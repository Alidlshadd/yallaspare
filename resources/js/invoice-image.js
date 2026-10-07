import '../css/invoice-image.css';
import { toBlob } from 'html-to-image';

/**
 * "Save as image" on the invoice picture page.
 *
 * Some customers want a picture they can keep in their chat, not a PDF. The
 * sheet on screen is the PDF's own layout; it is turned into a PNG in the
 * browser — the same text the browser already shaped and laid out, so Arabic
 * and Kurdish come out joined and right to left exactly as shown. Nothing is
 * sent to the server.
 *
 * On a phone the picture goes to the share sheet (WhatsApp is one tap away);
 * elsewhere it downloads.
 */
const sheet = document.querySelector('[data-invoice-sheet]');
const button = document.querySelector('[data-invoice-image]');

if (sheet && button) {
    const idleLabel = button.textContent;
    const filename = (button.dataset.filename || 'invoice') + '.png';

    const render = async () => {
        const options = {
            pixelRatio: 2,
            backgroundColor: '#ffffff',
            cacheBust: false,
            // The sheet sits centred on the page with a shadow; the picture
            // is the paper alone.
            style: { margin: '0', boxShadow: 'none' },
        };

        // The invoice's font has to be in before anything is measured, or
        // the picture is laid out in one face and drawn in another.
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }

        // Safari paints an empty canvas on the first pass of a page it has
        // not rasterised before; the second pass has what it needs.
        await toBlob(sheet, options);

        return toBlob(sheet, options);
    };

    const save = (blob) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 10000);
    };

    const run = async (viaGesture) => {
        if (button.disabled) return;

        button.disabled = true;
        button.textContent = button.dataset.busyLabel || idleLabel;

        try {
            const blob = await render();

            if (!blob) throw new Error('empty image');

            const file = new File([blob], filename, { type: 'image/png' });
            const touch = window.matchMedia('(pointer: coarse)').matches;

            // Sharing needs a tap; an automatic run can only download.
            if (viaGesture && touch && navigator.canShare && navigator.canShare({ files: [file] })) {
                try {
                    await navigator.share({ files: [file], title: filename });
                } catch (error) {
                    if (error.name !== 'AbortError') save(blob);
                }
            } else {
                save(blob);
            }

            button.textContent = idleLabel;
        } catch (error) {
            button.textContent = button.dataset.failedLabel || idleLabel;
        } finally {
            button.disabled = false;
        }
    };

    button.addEventListener('click', () => run(true));

    if (button.dataset.auto === '1') {
        run(false);
    }
}
