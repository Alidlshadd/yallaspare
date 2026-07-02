@once
    <script nonce="{{ $cspNonce }}">
        (() => {
            const languageDropdowns = Array.from(document.querySelectorAll('[data-header-dropdown]'));

            const closeLanguageDropdowns = (except = null) => {
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

            languageDropdowns.forEach((root) => {
                if (root.dataset.languageDropdownReady === '1') {
                    return;
                }

                root.dataset.languageDropdownReady = '1';

                const trigger = root.querySelector('[data-header-dropdown-trigger]');
                const menu = root.querySelector('[data-header-dropdown-menu]');
                const icon = root.querySelector('[data-header-dropdown-icon]');

                if (!trigger || !menu) {
                    return;
                }

                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const willOpen = menu.classList.contains('hidden');
                    closeLanguageDropdowns(root);
                    menu.classList.toggle('hidden', !willOpen);
                    trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    icon?.classList.toggle('rotate-180', willOpen);
                });

                menu.addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            });

            document.addEventListener('click', (event) => {
                const target = event.target instanceof Node ? event.target : null;

                if (!target || !languageDropdowns.some((root) => root.contains(target))) {
                    closeLanguageDropdowns();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeLanguageDropdowns();
                }
            });
        })();
    </script>
@endonce
