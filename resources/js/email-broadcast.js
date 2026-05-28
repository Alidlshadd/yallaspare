import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

export function initEmailBroadcast() {
    document.querySelectorAll('[data-tiptap-mount]').forEach((mount) => {
        if (mount.dataset.tiptapReady === '1') return;
        mount.dataset.tiptapReady = '1';

        const hidden = mount.parentElement?.querySelector('input[type="hidden"][name="body_html"]');
        const editor = new Editor({
            element: mount,
            extensions: [
                StarterKit,
                Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener', target: '_blank' } }),
                Image,
            ],
            content: hidden?.value || '<p></p>',
            onUpdate: ({ editor }) => {
                if (hidden) hidden.value = editor.getHTML();
            },
            editorProps: {
                attributes: {
                    class: 'prose prose-sm max-w-none min-h-[260px] rounded-lg border border-slate-300 bg-white p-3 text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100',
                },
            },
        });

        mount._tiptap = editor;
    });
}

if (typeof window !== 'undefined') {
    window.addEventListener('DOMContentLoaded', initEmailBroadcast);
}
