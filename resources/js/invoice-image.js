import { toBlob } from 'html-to-image';

/**
 * "Save as image" on an invoice page.
 *
 * Some customers want a picture they can keep in their chat, not a PDF. The
 * invoice on screen is turned into a PNG in the browser — the same text the
 * browser already shaped and laid out, so Arabic and Kurdish come out joined
 * and right to left exactly as shown. Nothing is sent to the server.
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
            backgroundColor: '#f3f4f7',
            cacheBust: false,
            // The page's web fonts are on another host the picture cannot
            // reach; the system's own Arabic-script font takes over.
            skipFonts: true,
            filter: (node) => !(node.classList && node.classList.contains('no-print')),
        };

        // Lay the sheet out in the font the picture will be drawn in, and
        // give the browser a frame to do it, before anything is measured.
        sheet.classList.add('capturing');
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

        try {
            // Safari paints an empty canvas on the first pass of a page it
            // has not rasterised before; the second pass has what it needs.
            await toBlob(sheet, options);

            return await toBlob(sheet, options);
        } finally {
            sheet.classList.remove('capturing');
        }
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
        // Fonts change line heights; wait for them so the picture matches.
        (document.fonts ? document.fonts.ready : Promise.resolve()).then(() => run(false));
    }
}
