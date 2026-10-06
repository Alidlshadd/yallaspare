{{-- Tells the admin a picture will be refused before the whole form is sent.
     The server checks the same things again; this only saves the round trip. --}}
<p id="productImageClientError" role="alert" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>

<script nonce="{{ $cspNonce }}">
    (function () {
        const errorBox = document.getElementById('productImageClientError');
        const maxBytes = @json(\App\Support\ProductImageUpload::maxKilobytes() * 1024);
        const allowedTypes = @json(\App\Support\ProductImageUpload::MIME_TYPES);
        const allowedExtensions = @json(\App\Support\ProductImageUpload::EXTENSIONS);
        const messages = {
            tooLarge: @json(__('The image is too large. The maximum size is :limit MB per image.', ['limit' => \App\Support\ProductImageUpload::maxMegabytes()])),
            unsupported: @json(__('Unsupported image format. Please upload a JPG, PNG or WEBP file.')),
        };

        const isSupported = (file) => {
            if (file.type) {
                return allowedTypes.includes(file.type);
            }

            return allowedExtensions.includes((file.name.split('.').pop() || '').toLowerCase());
        };

        ['productImage', 'gallery_images'].forEach((id) => {
            const input = document.getElementById(id);
            if (!input || !errorBox) {
                return;
            }

            // Capture phase, so the input is already emptied when the preview
            // script reads it.
            input.addEventListener('change', () => {
                const files = Array.from(input.files || []);
                const rejected = files.find((file) => !isSupported(file))
                    || files.find((file) => file.size > maxBytes);

                if (!rejected) {
                    errorBox.classList.add('hidden');
                    errorBox.textContent = '';
                    return;
                }

                errorBox.textContent = rejected.name + ' — ' + (isSupported(rejected) ? messages.tooLarge : messages.unsupported);
                errorBox.classList.remove('hidden');
                input.value = '';
            }, true);
        });
    })();
</script>
