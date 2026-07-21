@php
    $storePopup = \App\Models\Popup::activeForPage(
        \App\Models\Popup::pageKeyForRoute(request()->route()?->getName())
    );
@endphp

@if($storePopup)
    @php
        $popupImageUrl = ! empty($storePopup->image_path)
            ? asset('storage/' . ltrim($storePopup->image_path, '/'))
            : null;
    @endphp
    {{-- Inline display:none (not the hidden attribute) so the .flex utility can never win the cascade before JS opens it --}}
    <div
        style="display: none;"
        data-store-popup
        data-popup-id="{{ $storePopup->id }}"
        data-popup-delay="{{ $storePopup->delay_seconds }}"
        data-popup-frequency="{{ $storePopup->frequency }}"
        data-popup-frequency-days="{{ $storePopup->frequency_days }}"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $storePopup->localizedTitle() }}"
    >
        <div data-popup-close class="absolute inset-0 bg-[#0a1533]/60 backdrop-blur-[2px]"></div>

        <div
            data-popup-card
            class="relative flex min-h-[19rem] w-full max-w-md flex-col justify-end overflow-hidden rounded-3xl shadow-[0_40px_90px_-30px_rgba(5,12,30,0.65)] sm:min-h-[24rem] sm:max-w-lg lg:min-h-[28rem] lg:max-w-xl"
            style="background: linear-gradient(160deg, #0a1533 0%, #1a2f5f 45%, #35558f 100%);"
        >
            @if($popupImageUrl)
                <img src="{{ $popupImageUrl }}" alt="" class="absolute inset-0 h-full w-full object-contain">
            @endif
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[#060c1c]/90"></div>

            <button
                type="button"
                data-popup-close
                aria-label="{{ __('Close') }}"
                class="absolute top-3 z-10 grid h-9 w-9 place-items-center rounded-full bg-white/95 text-[#14213d] shadow-lg transition hover:bg-white ltr:right-3 rtl:left-3 lg:h-10 lg:w-10"
            >
                <svg class="h-4 w-4 lg:h-[1.1rem] lg:w-[1.1rem]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="relative flex flex-col gap-3 p-6 pb-7 sm:p-7 lg:gap-4 lg:p-9 lg:pb-10">
                <h2 class="store-title text-2xl font-bold leading-tight text-white sm:text-[1.65rem] lg:text-[2rem]">{{ $storePopup->localizedTitle() }}</h2>
                @if($storePopup->localizedDescription())
                    <p class="text-sm leading-relaxed text-white/80 lg:text-base">{{ $storePopup->localizedDescription() }}</p>
                @endif
                @if($storePopup->hasButton())
                    <a
                        href="{{ $storePopup->button_url }}"
                        data-popup-action
                        class="mt-2 inline-block self-start rounded-full bg-[#e85d2a] px-6 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(232,93,42,0.8)] transition hover:bg-[#b83a14] lg:px-7 lg:py-3 lg:text-base"
                    >{{ $storePopup->localizedButtonLabel() }}</a>
                @endif
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        (() => {
            const root = document.querySelector('[data-store-popup]');
            if (!root) {
                return;
            }

            const id = root.dataset.popupId;
            const sessionKey = 'ys_popup_seen_' + id;
            const localKey = 'ys_popup_last_' + id;
            const frequency = root.dataset.popupFrequency;
            const frequencyDays = Math.max(1, parseInt(root.dataset.popupFrequencyDays || '7', 10));
            const delayMs = Math.max(0, parseInt(root.dataset.popupDelay || '0', 10)) * 1000;

            const storageGet = (storage, key) => {
                try { return storage.getItem(key); } catch (e) { return null; }
            };
            const storageSet = (storage, key, value) => {
                try { storage.setItem(key, value); } catch (e) { /* private mode */ }
            };

            const shouldShow = () => {
                if (frequency === 'once_per_session') {
                    return storageGet(sessionStorage, sessionKey) === null;
                }
                if (frequency === 'once_per_days') {
                    const last = parseInt(storageGet(localStorage, localKey) || '0', 10);
                    return !last || (Date.now() - last) > frequencyDays * 86400000;
                }
                return true;
            };

            const markShown = () => {
                storageSet(sessionStorage, sessionKey, '1');
                storageSet(localStorage, localKey, String(Date.now()));
            };

            if (!shouldShow()) {
                root.remove();
                return;
            }

            const card = root.querySelector('[data-popup-card]');
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let escListener = null;

            const close = () => {
                root.remove();
                document.body.style.removeProperty('overflow');
                if (escListener) {
                    document.removeEventListener('keydown', escListener);
                }
            };

            const open = () => {
                markShown();
                root.style.removeProperty('display');
                document.body.style.overflow = 'hidden';

                if (!reduceMotion && card) {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(18px) scale(0.98)';
                    card.style.transition = 'opacity 420ms ease-out, transform 420ms ease-out';
                    requestAnimationFrame(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0) scale(1)';
                    });
                }

                root.querySelector('[data-popup-close][type="button"]')?.focus();

                escListener = (event) => {
                    if (event.key === 'Escape') {
                        close();
                    }
                };
                document.addEventListener('keydown', escListener);
            };

            root.querySelectorAll('[data-popup-close]').forEach((el) => {
                el.addEventListener('click', close);
            });
            root.querySelector('[data-popup-action]')?.addEventListener('click', () => {
                document.body.style.removeProperty('overflow');
            });

            window.setTimeout(open, delayMs);
        })();
    </script>
@endif
