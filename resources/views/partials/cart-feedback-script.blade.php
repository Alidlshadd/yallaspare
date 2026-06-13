<style>
    .cart-flyer {
        position: fixed;
        z-index: 9999;
        border-radius: 1rem;
        object-fit: contain;
        pointer-events: none;
        background: #ffffff;
        box-shadow: 0 18px 36px -18px rgba(15, 23, 42, 0.45);
        opacity: 0.96;
        transform: translate3d(0, 0, 0) scale(1);
        transition: transform 720ms cubic-bezier(0.22, 1, 0.36, 1), opacity 720ms ease;
        will-change: transform, opacity;
    }

    .cart-toast {
        position: fixed;
        inset-inline: 1rem;
        bottom: 1rem;
        z-index: 10000;
        margin-inline: auto;
        max-width: 24rem;
        border-radius: 0.875rem;
        background: #070b1f;
        color: #ffffff;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        text-align: center;
        box-shadow: 0 24px 55px -28px rgba(2, 6, 23, 0.75);
        opacity: 0;
        transform: translateY(0.75rem) scale(0.98);
        transition: opacity 180ms ease, transform 180ms ease;
        pointer-events: none;
    }

    .cart-toast.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .cart-button-added {
        background-color: #15803d !important;
        color: #ffffff !important;
    }

    .cart-badge-bump {
        animation: cartBadgeBump 560ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes cartBadgeBump {
        0% { transform: scale(1); }
        35% { transform: scale(1.45); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }

    @media (prefers-reduced-motion: reduce) {
        .cart-flyer { display: none; }
        .cart-toast { transition: none; }
        .cart-badge-bump { animation: none; }
    }
</style>

<script>
    (() => {
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
            toast.textContent = message || 'Product added to cart.';
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
            button.textContent = 'Added';
            button.classList.add('cart-button-added');
            window.clearTimeout(Number(button.dataset.resetTimer || 0));
            button.dataset.resetTimer = String(window.setTimeout(() => {
                button.textContent = original;
                button.classList.remove('cart-button-added');
                delete button.dataset.resetTimer;
            }, 1500));
        };
        const animateToCart = (form) => {
            const image = (form.closest('article') || form.closest('section') || document).querySelector('img');
            const target = visible(cartBadges());
            if (!(image instanceof HTMLImageElement) || !image.currentSrc || !target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                bumpBadge();
                return;
            }
            const sourceRect = image.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            if (sourceRect.width <= 0 || sourceRect.height <= 0) {
                bumpBadge();
                return;
            }
            const size = Math.min(92, Math.max(48, Math.min(sourceRect.width, sourceRect.height)));
            const flyer = document.createElement('img');
            flyer.src = image.currentSrc;
            flyer.alt = '';
            flyer.setAttribute('aria-hidden', 'true');
            flyer.className = 'cart-flyer';
            flyer.style.width = `${size}px`;
            flyer.style.height = `${size}px`;
            flyer.style.left = `${sourceRect.left + (sourceRect.width / 2) - (size / 2)}px`;
            flyer.style.top = `${sourceRect.top + (sourceRect.height / 2) - (size / 2)}px`;
            document.body.appendChild(flyer);
            const dx = targetRect.left + (targetRect.width / 2) - (sourceRect.left + (sourceRect.width / 2));
            const dy = targetRect.top + (targetRect.height / 2) - (sourceRect.top + (sourceRect.height / 2));
            window.requestAnimationFrame(() => {
                flyer.style.transform = `translate3d(${dx}px, ${dy}px, 0) scale(0.18) rotate(10deg)`;
                flyer.style.opacity = '0.15';
            });
            window.setTimeout(() => {
                flyer.remove();
                bumpBadge();
            }, 760);
        };

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
            const previousScrollY = window.scrollY || document.documentElement.scrollTop || 0;
            if (button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            }

            try {
                const response = await fetch(submitter?.formAction || form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Cart request failed');
                }

                let payload = null;
                let nextCount = currentCartCount() + 1;
                if ((response.headers.get('content-type') || '').includes('application/json')) {
                    payload = await response.json();
                    if (Number.isInteger(payload?.cart_count)) {
                        nextCount = payload.cart_count;
                    }
                }

                setCartSummary(nextCount, payload);
                markButtonAdded(button);
                showToast(payload?.message);
                animateToCart(form);
            } catch (error) {
                showToast('Could not add product. Please try again.');
            } finally {
                window.scrollTo(0, previousScrollY);
                if (button) {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                }
            }
        }, true);
    })();
</script>
