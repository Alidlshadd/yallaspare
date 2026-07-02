<style>
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
        transform: translateY(-1px);
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
        .cart-toast { transition: none; }
        .cart-badge-bump { animation: none; }
    }
</style>

<script nonce="{{ $cspNonce }}">
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
    })();
</script>
