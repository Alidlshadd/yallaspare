// Storefront front-end behaviour, moved out of inline Blade <script> blocks so
// the page source stays compact and the logic ships minified in the Vite
// bundle. Every initializer guards on the presence of its markup, so this file
// is safe to load on any storefront page (all extend layouts/user).
//
// Server data reaches this module the same way it did inline: via data-*
// attributes on the elements, plus a small translation config exposed as
// window.YallaI18n from the layout. Nothing here is a security boundary.

const initHeroVideos = () => {
    const videos = Array.from(document.querySelectorAll('[data-hero-background-video]'));
    if (videos.length === 0) {
        return;
    }

    const progressState = new WeakMap();

    const setHeroVideoVisible = (video, visible) => {
        if (!(video instanceof HTMLVideoElement)) {
            return;
        }

        video.classList.toggle('opacity-100', visible);
        video.classList.toggle('opacity-0', !visible);
    };

    const prepareHeroVideo = (video) => {
        if (!(video instanceof HTMLVideoElement)) {
            return false;
        }

        video.muted = true;
        video.defaultMuted = true;
        video.loop = true;
        video.autoplay = true;
        video.playsInline = true;
        video.controls = false;
        video.volume = 0;
        video.playbackRate = 1;
        video.setAttribute('muted', '');
        video.setAttribute('autoplay', '');
        video.setAttribute('loop', '');
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.setAttribute('disablepictureinpicture', '');
        video.setAttribute('disableremoteplayback', '');
        video.setAttribute('controlslist', 'nodownload nofullscreen noremoteplayback');
        video.setAttribute('x-webkit-airplay', 'deny');
        video.setAttribute('aria-hidden', 'true');
        video.setAttribute('tabindex', '-1');
        video.removeAttribute('controls');

        return true;
    };

    const playHeroVideo = (video, options = {}) => {
        if (!prepareHeroVideo(video) || document.hidden) {
            setHeroVideoVisible(video, false);
            return;
        }

        if (options.reload && video.readyState === HTMLMediaElement.HAVE_NOTHING) {
            try {
                video.load();
            } catch (error) {}
        }

        if (video.ended || options.restart) {
            video.currentTime = 0;
        }

        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(() => {
                setHeroVideoVisible(video, false);
                if (!document.hidden) {
                    window.setTimeout(() => playHeroVideo(video), 250);
                }
            });
        }
    };

    const restartHeroVideo = (video, delay = 40, options = {}) => {
        setHeroVideoVisible(video, false);
        window.setTimeout(() => playHeroVideo(video, options), delay);
    };

    const resumeAllHeroVideos = () => {
        videos.forEach((video) => playHeroVideo(video));
    };

    const recoverFrozenHeroVideo = (video) => {
        if (!prepareHeroVideo(video) || document.hidden) {
            return;
        }

        const now = Date.now();
        const currentTime = Number.isFinite(video.currentTime) ? video.currentTime : 0;
        const previous = progressState.get(video) || {
            checkedAt: now,
            currentTime,
            frozenCount: 0,
        };
        const progressed = Math.abs(currentTime - previous.currentTime) > 0.04;
        const unhealthy = video.paused
            || video.ended
            || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA
            || video.networkState === HTMLMediaElement.NETWORK_NO_SOURCE;
        let frozenCount = progressed ? 0 : previous.frozenCount + 1;

        if (unhealthy || frozenCount >= 3) {
            setHeroVideoVisible(video, false);

            const shouldReload = video.networkState === HTMLMediaElement.NETWORK_NO_SOURCE
                || video.readyState === HTMLMediaElement.HAVE_NOTHING;

            playHeroVideo(video, { reload: shouldReload, restart: video.ended });
            frozenCount = 0;
        } else if (!video.paused && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
            setHeroVideoVisible(video, true);
        }

        progressState.set(video, {
            checkedAt: now,
            currentTime,
            frozenCount,
        });
    };

    videos.forEach((video) => {
        playHeroVideo(video);

        [
            'loadedmetadata',
            'loadeddata',
            'canplay',
            'canplaythrough',
        ].forEach((eventName) => {
            video.addEventListener(eventName, () => playHeroVideo(video), { passive: true });
        });

        ['playing', 'timeupdate'].forEach((eventName) => {
            video.addEventListener(eventName, () => {
                if (!video.paused && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                    setHeroVideoVisible(video, true);
                }
            }, { passive: true });
        });

        [
            'pause',
            'ended',
            'stalled',
            'suspend',
            'waiting',
            'emptied',
            'abort',
        ].forEach((eventName) => {
            video.addEventListener(eventName, () => restartHeroVideo(video), { passive: true });
        });

        ['volumechange', 'ratechange'].forEach((eventName) => {
            video.addEventListener(eventName, () => restartHeroVideo(video), { passive: true });
        });

        ['webkitbeginfullscreen', 'enterpictureinpicture'].forEach((eventName) => {
            video.addEventListener(eventName, (event) => {
                if (typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }

                restartHeroVideo(video);
            });
        });
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            resumeAllHeroVideos();
        }
    });

    ['pageshow', 'focus', 'resize', 'orientationchange'].forEach((eventName) => {
        window.addEventListener(eventName, resumeAllHeroVideos, { passive: true });
    });

    ['touchstart', 'touchend', 'pointerdown', 'pointerup', 'click', 'scroll'].forEach((eventName) => {
        window.addEventListener(eventName, () => resumeAllHeroVideos(), { passive: true });
    });

    window.setInterval(() => {
        if (document.hidden) {
            return;
        }

        videos.forEach((video) => {
            recoverFrozenHeroVideo(video);
        });
    }, 500);
};

// Shared by the home hero finder and the shop listing finder — both use the
// same [data-vehicle-finder] markup.
const initVehicleFinder = () => {
    document.querySelectorAll('[data-vehicle-finder]').forEach((form) => {
        const brandSelect = form.querySelector('[data-vehicle-brand]');
        const modelSelect = form.querySelector('[data-vehicle-model]');

        if (!brandSelect || !modelSelect) {
            return;
        }

        const modelMap = JSON.parse(form.dataset.modelMap || '{}');
        const selectedModel = modelSelect.value;
        const modelPlaceholder = form.dataset.modelPlaceholder || 'Model';
        const allModelsPlaceholder = form.dataset.allModelsPlaceholder || 'Select brand first';
        const noModelsPlaceholder = form.dataset.noModelsPlaceholder || 'No models for this brand yet';
        const hasStructuredModels = Object.keys(modelMap).length > 0;

        const setOptions = () => {
            if (!hasStructuredModels) {
                return;
            }

            const brand = brandSelect.value;
            const models = brand ? (modelMap[brand] || []) : [];
            modelSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = brand ? modelPlaceholder : allModelsPlaceholder;
            modelSelect.appendChild(placeholder);

            models.forEach((model) => {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                option.selected = model === selectedModel;
                modelSelect.appendChild(option);
            });

            modelSelect.disabled = brand === '' || models.length === 0;
            if (brand !== '' && models.length === 0) {
                placeholder.textContent = noModelsPlaceholder;
            }
        };

        brandSelect.addEventListener('change', () => {
            modelSelect.value = '';
            setOptions();
        });

        setOptions();
    });
};

const initWishlistForms = () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    const wishlistBadges = () => Array.from(document.querySelectorAll('[data-wishlist-count-badge]'));

    const currentWishlistCount = () => {
        const badge = wishlistBadges()[0];
        if (!badge) return 0;
        const raw = Number.parseInt(badge.dataset.wishlistCountValue || '0', 10);
        return Number.isNaN(raw) ? 0 : Math.max(0, raw);
    };

    const setWishlistCount = (count) => {
        const normalized = Math.max(0, count);
        wishlistBadges().forEach((badge) => {
            badge.dataset.wishlistCountValue = String(normalized);
            badge.textContent = normalized > 99 ? '99+' : String(normalized);
        });
    };

    const ensureMethodInput = (form, method) => {
        let methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput && method) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            form.appendChild(methodInput);
        }
        if (methodInput) {
            if (method) {
                methodInput.value = method;
            } else {
                methodInput.remove();
            }
        }
    };

    const setButtonState = (form, isWishlisted) => {
        const button = form.querySelector('.js-wishlist-button');
        if (!button) return;

        form.dataset.wishlisted = isWishlisted ? '1' : '0';
        form.action = isWishlisted ? form.dataset.destroyUrl : form.dataset.storeUrl;
        ensureMethodInput(form, isWishlisted ? 'DELETE' : '');

        button.setAttribute('aria-label', isWishlisted ? 'Remove from wishlist' : 'Add to wishlist');
        button.classList.remove(
            'border-slate-200', 'text-slate-500', 'hover:border-primary/30', 'hover:text-primary',
            'focus-visible:ring-primary/20', 'dark:border-slate-700', 'dark:text-slate-400',
            'border-rose-200', 'text-rose-700', 'hover:bg-rose-50', 'focus-visible:ring-rose-300',
            'dark:border-rose-900/60', 'dark:text-rose-300'
        );

        if (isWishlisted) {
            button.classList.add(
                'border-rose-200', 'text-rose-700', 'hover:bg-rose-50', 'focus-visible:ring-rose-300',
                'dark:border-rose-900/60', 'dark:text-rose-300'
            );
        } else {
            button.classList.add(
                'border-slate-200', 'text-slate-500', 'hover:border-primary/30', 'hover:text-primary',
                'focus-visible:ring-primary/20', 'dark:border-slate-700', 'dark:text-slate-400'
            );
        }
    };

    document.querySelectorAll('.js-wishlist-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const currentlyWishlisted = form.dataset.wishlisted === '1';
            const button = form.querySelector('.js-wishlist-button');
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json, text/html',
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Wishlist request failed');
                }

                setButtonState(form, !currentlyWishlisted);
                setWishlistCount(currentWishlistCount() + (currentlyWishlisted ? -1 : 1));
            } catch (error) {
                form.submit();
            } finally {
                if (button) button.disabled = false;
            }
        });
    });
};

const initStickyHeader = () => {
    const header = document.querySelector('[data-store-header]');
    if (!header) {
        return;
    }

    let lastY = window.scrollY || 0;
    let directionBuffer = 0;
    let isVisible = true;
    let ticking = false;
    const minVisibleOffset = 96;
    const downThreshold = 30;
    const upThreshold = 16;

    const showHeader = () => {
        if (isVisible) {
            return;
        }

        isVisible = true;
        header.classList.remove('-translate-y-full');
        header.classList.add('translate-y-0');
    };

    const hideHeader = () => {
        if (!isVisible) {
            return;
        }

        isVisible = false;
        header.classList.remove('translate-y-0');
        header.classList.add('-translate-y-full');
    };

    const updateHeaderState = () => {
        const currentY = window.scrollY || 0;
        const delta = currentY - lastY;

        if (Math.abs(delta) < 4) {
            ticking = false;
            return;
        }

        if (currentY <= minVisibleOffset) {
            directionBuffer = 0;
            showHeader();
            lastY = currentY;
            ticking = false;
            return;
        }

        if (delta > 0) {
            directionBuffer = Math.max(0, directionBuffer) + delta;
            if (directionBuffer >= downThreshold) {
                hideHeader();
                directionBuffer = 0;
            }
        } else {
            directionBuffer = Math.min(0, directionBuffer) + delta;
            if (Math.abs(directionBuffer) >= upThreshold) {
                showHeader();
                directionBuffer = 0;
            }
        }

        lastY = currentY;
        ticking = false;
    };

    const requestTick = () => {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(updateHeaderState);
    };

    header.classList.add('translate-y-0');
    window.addEventListener('scroll', requestTick, { passive: true });
    window.addEventListener('resize', () => {
        if ((window.scrollY || 0) <= minVisibleOffset) {
            directionBuffer = 0;
            showHeader();
        }
    }, { passive: true });
};

const initSearchAutocomplete = () => {
    const forms = Array.from(document.querySelectorAll('[data-search-autocomplete]'));
    if (forms.length === 0) {
        return;
    }

    const i18n = window.YallaI18n || {};
    const labels = {
        products: i18n.products || 'Products',
        categories: i18n.categories || 'Categories',
        brands: i18n.brands || 'Brands',
        sku: i18n.sku || 'SKU:',
        inStock: i18n.inStock || 'In stock',
        outOfStock: i18n.outOfStock || 'Out of stock',
    };

    const makeText = (tag, className, text) => {
        const node = document.createElement(tag);
        node.className = className;
        node.textContent = text || '';
        return node;
    };

    const addSection = (panel, title, items, renderItem) => {
        if (!items || items.length === 0) {
            return;
        }

        panel.appendChild(makeText('div', 'px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400', title));
        const list = document.createElement('div');
        list.className = 'py-1';
        items.forEach((item) => list.appendChild(renderItem(item)));
        panel.appendChild(list);
    };

    const productRow = (item) => {
        const row = document.createElement('a');
        row.href = item.url;
        row.className = 'flex items-center gap-3 px-3 py-2.5 text-sm transition hover:bg-slate-50 dark:hover:bg-slate-900';

        const media = document.createElement('span');
        media.className = 'flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-400 dark:border-slate-800 dark:bg-slate-900';
        if (item.image_url) {
            const image = document.createElement('img');
            image.src = item.image_url;
            image.alt = item.label || '';
            image.className = 'h-full w-full object-contain';
            media.appendChild(image);
        } else {
            media.textContent = (item.label || '?').slice(0, 1).toUpperCase();
        }

        const body = document.createElement('span');
        body.className = 'min-w-0 flex-1';
        body.appendChild(makeText('span', 'block truncate font-semibold text-slate-900 dark:text-white', item.label));
        body.appendChild(makeText('span', 'mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400', `${labels.sku} ${item.sku || '-'} | ${item.price_formatted || ''}`));

        const stock = makeText('span', `shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold ${Number(item.stock_quantity || 0) > 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'}`, Number(item.stock_quantity || 0) > 0 ? labels.inStock : labels.outOfStock);

        row.append(media, body, stock);
        return row;
    };

    const simpleRow = (item, meta = '') => {
        const row = document.createElement('a');
        row.href = item.url;
        row.className = 'flex items-center justify-between gap-3 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-900';
        row.appendChild(makeText('span', 'truncate', item.label));
        if (meta) {
            row.appendChild(makeText('span', 'shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400', meta));
        }
        return row;
    };

    forms.forEach((form) => {
        const input = form.querySelector('[data-search-autocomplete-input]');
        const panel = form.querySelector('[data-search-autocomplete-panel]');
        const endpoint = form.dataset.searchAutocompleteUrl;
        let timer = null;
        let controller = null;

        if (!input || !panel || !endpoint) {
            return;
        }

        const hide = () => {
            panel.classList.add('hidden');
        };

        const render = (payload) => {
            panel.replaceChildren();
            addSection(panel, labels.products, payload.products || [], productRow);
            addSection(panel, labels.categories, payload.categories || [], (item) => simpleRow(item, item.product_count ? String(item.product_count) : ''));
            addSection(panel, labels.brands, payload.brands || [], (item) => simpleRow(item));

            if (panel.childElementCount === 0) {
                hide();
                return;
            }

            panel.classList.remove('hidden');
        };

        const search = () => {
            const query = input.value.trim();
            if (query.length < 2) {
                hide();
                return;
            }

            controller?.abort();
            controller = new AbortController();

            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', query);

            fetch(url, {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal,
            })
                .then((response) => response.ok ? response.json() : null)
                .then((json) => {
                    if (json?.data) {
                        render(json.data);
                    }
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        hide();
                    }
                });
        };

        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(search, 180);
        });
        input.addEventListener('focus', search);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hide();
            }
        });

        document.addEventListener('click', (event) => {
            if (!form.contains(event.target)) {
                hide();
            }
        });
    });
};

const initHeaderDropdowns = () => {
    const desktopQuery = window.matchMedia('(min-width: 1024px)');
    const languageDropdowns = Array.from(document.querySelectorAll('[data-header-dropdown]'));
    const accountDropdowns = Array.from(document.querySelectorAll('[data-header-account]'));
    const categoryTrigger = document.querySelector('[data-store-categories-trigger]');
    const categoryMenu = document.querySelector('[data-store-categories-menu]');
    const categoryIcon = document.querySelector('[data-store-categories-icon]');

    if (languageDropdowns.length === 0 && accountDropdowns.length === 0 && !categoryTrigger) {
        return;
    }

    let categoryCloseTimer = null;
    let languageCloseTimer = null;
    let accountCloseTimer = null;

    const closeLanguageDropdowns = (except = null) => {
        if (languageCloseTimer) {
            window.clearTimeout(languageCloseTimer);
            languageCloseTimer = null;
        }

        languageDropdowns.forEach((root) => {
            if (root === except) {
                return;
            }

            const menu = root.querySelector('[data-header-dropdown-menu]');
            const trigger = root.querySelector('[data-header-dropdown-trigger]');
            const icon = root.querySelector('[data-header-dropdown-icon]');

            menu?.classList.add('hidden');
            trigger?.setAttribute('aria-expanded', 'false');
            icon?.classList.remove('rotate-180');
        });
    };

    const closeCategoryMenu = () => {
        if (categoryCloseTimer) {
            window.clearTimeout(categoryCloseTimer);
            categoryCloseTimer = null;
        }

        categoryMenu?.classList.add('hidden');
        categoryTrigger?.setAttribute('aria-expanded', 'false');
        categoryIcon?.classList.remove('rotate-180');
    };

    const closeAccountDropdowns = (except = null) => {
        if (accountCloseTimer) {
            window.clearTimeout(accountCloseTimer);
            accountCloseTimer = null;
        }

        accountDropdowns.forEach((root) => {
            if (root === except) {
                return;
            }

            const menu = root.querySelector('[data-header-account-menu]');
            const trigger = root.querySelector('[data-header-account-trigger]');
            const icon = root.querySelector('[data-header-account-icon]');

            menu?.classList.add('hidden');
            trigger?.setAttribute('aria-expanded', 'false');
            icon?.classList.remove('rotate-180');
        });
    };

    const openLanguageDropdown = (root) => {
        const menu = root.querySelector('[data-header-dropdown-menu]');
        const trigger = root.querySelector('[data-header-dropdown-trigger]');
        const icon = root.querySelector('[data-header-dropdown-icon]');

        closeCategoryMenu();
        closeAccountDropdowns();
        closeLanguageDropdowns(root);
        menu?.classList.remove('hidden');
        trigger?.setAttribute('aria-expanded', 'true');
        icon?.classList.add('rotate-180');
    };

    const toggleLanguageDropdown = (root) => {
        const menu = root.querySelector('[data-header-dropdown-menu]');

        if (!menu || menu.classList.contains('hidden')) {
            openLanguageDropdown(root);
            return;
        }

        closeLanguageDropdowns();
    };

    const queueLanguageClose = () => {
        if (languageCloseTimer) {
            window.clearTimeout(languageCloseTimer);
        }

        languageCloseTimer = window.setTimeout(() => closeLanguageDropdowns(), 220);
    };

    const cancelLanguageClose = () => {
        if (languageCloseTimer) {
            window.clearTimeout(languageCloseTimer);
            languageCloseTimer = null;
        }
    };

    const openAccountDropdown = (root) => {
        const menu = root.querySelector('[data-header-account-menu]');
        const trigger = root.querySelector('[data-header-account-trigger]');
        const icon = root.querySelector('[data-header-account-icon]');

        closeCategoryMenu();
        closeLanguageDropdowns();
        closeAccountDropdowns(root);
        menu?.classList.remove('hidden');
        trigger?.setAttribute('aria-expanded', 'true');
        icon?.classList.add('rotate-180');
    };

    const toggleAccountDropdown = (root) => {
        const menu = root.querySelector('[data-header-account-menu]');

        if (!menu || menu.classList.contains('hidden')) {
            openAccountDropdown(root);
            return;
        }

        closeAccountDropdowns();
    };

    const queueAccountClose = () => {
        if (accountCloseTimer) {
            window.clearTimeout(accountCloseTimer);
        }

        accountCloseTimer = window.setTimeout(() => closeAccountDropdowns(), 220);
    };

    const cancelAccountClose = () => {
        if (accountCloseTimer) {
            window.clearTimeout(accountCloseTimer);
            accountCloseTimer = null;
        }
    };

    const openCategoryMenu = () => {
        if (!categoryMenu || !categoryTrigger) {
            return;
        }

        if (categoryCloseTimer) {
            window.clearTimeout(categoryCloseTimer);
            categoryCloseTimer = null;
        }

        closeLanguageDropdowns();
        closeAccountDropdowns();
        categoryMenu.classList.remove('hidden');
        categoryTrigger.setAttribute('aria-expanded', 'true');
        categoryIcon?.classList.add('rotate-180');
    };

    const queueCategoryClose = () => {
        if (!desktopQuery.matches) {
            return;
        }

        if (categoryCloseTimer) {
            window.clearTimeout(categoryCloseTimer);
        }

        categoryCloseTimer = window.setTimeout(closeCategoryMenu, 180);
    };

    languageDropdowns.forEach((root) => {
        const trigger = root.querySelector('[data-header-dropdown-trigger]');

        root.addEventListener('mouseenter', () => {
            cancelLanguageClose();
            openLanguageDropdown(root);
        });
        root.addEventListener('mouseleave', queueLanguageClose);

        trigger?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            toggleLanguageDropdown(root);
        });

        const menu = root.querySelector('[data-header-dropdown-menu]');

        menu?.addEventListener('mouseenter', cancelLanguageClose);
        menu?.addEventListener('mouseleave', queueLanguageClose);
        menu?.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    });

    accountDropdowns.forEach((root) => {
        const trigger = root.querySelector('[data-header-account-trigger]');
        const menu = root.querySelector('[data-header-account-menu]');

        root.addEventListener('mouseenter', () => {
            cancelAccountClose();
            openAccountDropdown(root);
        });
        root.addEventListener('mouseleave', queueAccountClose);

        trigger?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            toggleAccountDropdown(root);
        });

        menu?.addEventListener('mouseenter', cancelAccountClose);
        menu?.addEventListener('mouseleave', queueAccountClose);
        menu?.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    });

    if (categoryTrigger && categoryMenu) {
        const categoryRoot = categoryMenu.parentElement;

        categoryRoot?.addEventListener('mouseenter', () => {
            if (categoryCloseTimer) {
                window.clearTimeout(categoryCloseTimer);
                categoryCloseTimer = null;
            }
        });
        categoryRoot?.addEventListener('mouseleave', queueCategoryClose);
        categoryTrigger.addEventListener('mouseenter', () => {
            if (desktopQuery.matches) {
                openCategoryMenu();
            }
        });
        categoryTrigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (categoryMenu.classList.contains('hidden')) {
                openCategoryMenu();
            } else {
                closeCategoryMenu();
            }
        });
        categoryMenu.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }

    document.addEventListener('click', () => {
        closeLanguageDropdowns();
        closeAccountDropdowns();
        closeCategoryMenu();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLanguageDropdowns();
            closeAccountDropdowns();
            closeCategoryMenu();
        }
    });
};

const initProductGallery = () => {
    const mainImage = document.getElementById('product-main-image');
    const thumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            const imageSrc = thumb.getAttribute('data-image-src');
            if (mainImage && imageSrc) {
                mainImage.src = imageSrc;
            }

            thumbs.forEach((node) => {
                node.classList.remove('border-primary', 'bg-slate-100', 'dark:bg-slate-800');
                node.classList.add('border-slate-200', 'bg-white', 'dark:border-slate-800', 'dark:bg-slate-900');
            });

            thumb.classList.remove('border-slate-200', 'bg-white', 'dark:border-slate-800', 'dark:bg-slate-900');
            thumb.classList.add('border-primary', 'bg-slate-100', 'dark:bg-slate-800');
        });
    });

    const qtyInput = document.getElementById('purchase-qty');
    const qtyHiddenInputs = Array.from(document.querySelectorAll('#purchase-qty-hidden, #purchase-qty-hidden-guest'));
    const qtyMinus = document.querySelector('[data-qty-minus]');
    const qtyPlus = document.querySelector('[data-qty-plus]');
    const qtyStepper = qtyInput ? qtyInput.closest('[data-qty-stepper]') : null;

    const replayClass = (node, className) => {
        if (!node) return;
        node.classList.remove(className);
        void node.offsetWidth;
        node.classList.add(className);
    };

    const readMaxQty = () => Math.max(1, Number.parseInt(qtyInput.dataset.maxQuantity || qtyInput.getAttribute('max') || '99', 10) || 99);

    const syncQty = () => {
        if (!qtyInput) return;
        const maxQty = readMaxQty();
        let value = Number.parseInt(qtyInput.value, 10);
        if (Number.isNaN(value) || value < 1) value = 1;
        if (value > maxQty) value = maxQty;
        qtyInput.value = value;
        qtyHiddenInputs.forEach((input) => {
            input.value = value;
        });
    };

    const stepQty = (delta, button) => {
        const before = Number.parseInt(qtyInput.value, 10) || 1;
        const next = Math.min(readMaxQty(), Math.max(1, before + delta));

        replayClass(button, 'qty-ripple');

        if (next === before) {
            replayClass(qtyStepper, 'qty-shake');
            return;
        }

        qtyInput.value = next;
        syncQty();
        replayClass(qtyInput, 'qty-bump');
    };

    if (qtyMinus && qtyInput) {
        qtyMinus.addEventListener('click', () => stepQty(-1, qtyMinus));
    }

    if (qtyPlus && qtyInput) {
        qtyPlus.addEventListener('click', () => stepQty(1, qtyPlus));
    }

    if (qtyInput) {
        qtyInput.addEventListener('change', syncQty);
        qtyInput.addEventListener('input', syncQty);
    }

    syncQty();
};

// Add-to-cart feedback: optimistic badge/label updates + toast, moved out of
// the cart-feedback-script partial. Reads translations from window.YallaI18n
// and the optional loading helper from window.YallaLoading (both set elsewhere;
// this degrades gracefully when they are absent).
const initCartFeedback = () => {
    if (window.YallaCartFeedbackInitialized) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        return;
    }

    window.YallaCartFeedbackInitialized = true;

    const visible = (elements) => elements.find((element) => {
        if (!(element instanceof HTMLElement)) return false;
        const rect = element.getBoundingClientRect();
        const styles = window.getComputedStyle(element);
        return rect.width > 0 && rect.height > 0 && styles.visibility !== 'hidden' && styles.display !== 'none';
    });
    const cartBadges = () => Array.from(document.querySelectorAll('[data-cart-count-badge]'));
    const cartItemsLabels = () => Array.from(document.querySelectorAll('[data-cart-items-label]'));
    const cartRefs = () => Array.from(document.querySelectorAll('[data-cart-ref]'));
    const cartTotals = () => Array.from(document.querySelectorAll('[data-cart-total]'));
    const currentCartCount = () => {
        const badge = cartBadges()[0];
        const raw = Number.parseInt(badge?.dataset.cartCountValue || '0', 10);
        return Number.isNaN(raw) ? 0 : Math.max(0, raw);
    };
    const setCartSummary = (count, payload = null) => {
        const normalized = Math.max(0, count);
        cartBadges().forEach((badge) => {
            badge.dataset.cartCountValue = String(normalized);
            badge.textContent = normalized > 99 ? '99+' : String(normalized);
        });
        cartItemsLabels().forEach((element) => {
            element.textContent = payload?.cart_items_label || `Items (${normalized})`;
        });
        if (typeof payload?.cart_ref === 'string' && payload.cart_ref !== '') {
            cartRefs().forEach((element) => { element.textContent = payload.cart_ref; });
        }
        if (typeof payload?.cart_total_formatted === 'string' && payload.cart_total_formatted !== '') {
            cartTotals().forEach((element) => { element.textContent = payload.cart_total_formatted; });
        }
    };
    const showToast = (message) => {
        document.querySelector('.cart-toast')?.remove();
        const toast = document.createElement('div');
        toast.className = 'cart-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.textContent = message || 'Added to cart successfully';
        document.body.appendChild(toast);
        window.requestAnimationFrame(() => toast.classList.add('is-visible'));
        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 2200);
    };
    const bumpBadge = () => {
        const badge = visible(cartBadges());
        if (!badge) return;
        badge.classList.remove('cart-badge-bump');
        window.requestAnimationFrame(() => badge.classList.add('cart-badge-bump'));
    };
    const markButtonAdded = (button) => {
        if (!button) return;
        const original = button.dataset.originalText || button.textContent.trim();
        button.dataset.originalText = original;
        button.textContent = button.dataset.addedText || 'Added ✓';
        button.classList.add('cart-button-added');
        window.clearTimeout(Number(button.dataset.resetTimer || 0));
        button.dataset.resetTimer = String(window.setTimeout(() => {
            button.textContent = original;
            button.classList.remove('cart-button-added');
            delete button.dataset.resetTimer;
        }, 1500));
    };
    const t = (key, fallback) => (window.YallaI18n && window.YallaI18n[key]) || fallback;
    const Loader = () => window.YallaLoading || null;

    document.addEventListener('submit', async (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : event.target?.closest?.('form');
        if (!form || !form.classList.contains('js-add-cart-form')) {
            return;
        }

        const submitter = event.submitter;
        if (submitter && !submitter.classList.contains('js-add-cart-button')) {
            return;
        }

        event.preventDefault();

        const button = form.querySelector('.js-add-cart-button');

        // Synchronous double-click guard: runs before fetch starts.
        if (button && (button.dataset.inFlight === 'true' || button.dataset.cooldownActive === 'true')) {
            return;
        }
        if (button) {
            button.dataset.inFlight = 'true';
        }

        const previousScrollY = window.scrollY || document.documentElement.scrollTop || 0;
        const loader = Loader();

        if (loader) {
            loader.setButtonLoading(button, true, t('adding', 'Adding...'));
        } else if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        }

        if (loader) {
            loader.showSlowRequestLoading();
        }

        let inCooldown = false;

        try {
            const action = submitter?.hasAttribute?.('formaction') ? submitter.formAction : form.action;

            const response = await fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
                credentials: 'same-origin',
            });

            if (loader) {
                loader.hideSlowRequestLoading();
            }

            if (response.status === 429) {
                const headerValue = parseInt(response.headers.get('Retry-After'), 10);
                const seconds = Number.isFinite(headerValue) && headerValue > 0 ? headerValue : 10;
                inCooldown = true;
                if (loader) {
                    loader.showRateLimitCooldown(seconds, button);
                }
                return;
            }

            let payload = null;
            let nextCount = currentCartCount() + 1;
            if ((response.headers.get('content-type') || '').includes('application/json')) {
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }
            }

            if (!response.ok) {
                throw new Error(payload?.message || 'Could not add product. Please try again.');
            }

            if (payload) {
                if (Number.isInteger(payload?.cart_count)) {
                    nextCount = payload.cart_count;
                }
            }

            if (loader) {
                loader.setButtonLoading(button, false);
            }
            setCartSummary(nextCount, payload);
            markButtonAdded(button);
            showToast(payload?.message || 'Added to cart successfully');
            bumpBadge();
        } catch (error) {
            if (loader) {
                loader.hideSlowRequestLoading();
                loader.setButtonLoading(button, false);
            }
            showToast(error?.message || 'Could not add product. Please try again.');
        } finally {
            window.scrollTo(0, previousScrollY);
            if (button) {
                delete button.dataset.inFlight;
                if (!inCooldown && button.dataset.cooldownActive !== 'true') {
                    if (loader) {
                        loader.setButtonLoading(button, false);
                    } else {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                }
            }
        }
    }, true);
};

const initCategoryRails = () => {
    document.querySelectorAll('[data-category-rail]').forEach((rail) => {
        const scroller = rail.querySelector('[data-category-scroll]');
        if (!scroller) {
            return;
        }

        const prev = rail.querySelector('[data-category-prev]');
        const next = rail.querySelector('[data-category-next]');
        const fadeStart = rail.querySelector('[data-category-fade-start]');
        const fadeEnd = rail.querySelector('[data-category-fade-end]');
        const isRtl = (document.documentElement.getAttribute('dir') || 'ltr') === 'rtl';

        const sync = () => {
            const max = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
            const position = Math.min(Math.abs(scroller.scrollLeft), max);
            const atStart = position <= 1;
            const atEnd = position >= max - 1;

            fadeStart?.classList.toggle('opacity-0', atStart);
            fadeEnd?.classList.toggle('opacity-0', atEnd);

            if (prev) {
                prev.disabled = atStart;
            }

            if (next) {
                next.disabled = atEnd;
            }
        };

        const scrollByStep = (direction) => {
            const step = Math.max(180, Math.round(scroller.clientWidth * 0.6));
            scroller.scrollBy({ left: step * direction * (isRtl ? -1 : 1), behavior: 'smooth' });
        };

        prev?.addEventListener('click', () => scrollByStep(-1));
        next?.addEventListener('click', () => scrollByStep(1));
        scroller.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    });
};

// Vision page (/vision): reveal-on-scroll sections and count-up stats. This
// page is the one storefront page allowed to be animation-heavy; everything
// still collapses to a static layout for reduced-motion users.
const initVisionPage = () => {
    const page = document.querySelector('[data-vision-page]');

    if (!page) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || document.documentElement.classList.contains('user-reduced-motion');
    const numberLocale = (document.documentElement.lang || 'en').startsWith('ar') ? 'ar' : 'en-US';
    const formatCount = (value) => value.toLocaleString(numberLocale);

    const runCounter = (element) => {
        if (element.dataset.visionCountDone) {
            return;
        }

        element.dataset.visionCountDone = '1';
        const target = Number.parseInt(element.dataset.visionCount || '0', 10) || 0;

        if (reducedMotion) {
            element.textContent = formatCount(target);
            return;
        }

        const duration = 1400;
        const startedAt = performance.now();

        const step = (now) => {
            const progress = Math.min((now - startedAt) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = formatCount(Math.round(target * eased));

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    };

    const revealTargets = Array.from(page.querySelectorAll('[data-vision-reveal]'));

    if (reducedMotion || typeof IntersectionObserver === 'undefined') {
        revealTargets.forEach((element) => element.classList.add('vs-in'));
        page.querySelectorAll('[data-vision-count]').forEach(runCounter);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('vs-in');
            entry.target.querySelectorAll('[data-vision-count]').forEach(runCounter);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.18 });

    revealTargets.forEach((element) => observer.observe(element));
};

const boot = () => {
    initHeroVideos();
    initVehicleFinder();
    initWishlistForms();
    initStickyHeader();
    initSearchAutocomplete();
    initHeaderDropdowns();
    initProductGallery();
    initCartFeedback();
    initCategoryRails();
    initVisionPage();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
