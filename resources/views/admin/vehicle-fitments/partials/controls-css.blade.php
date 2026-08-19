{{-- Shared control styles for the Vehicle Finder index and the variant pages.
     Included inside each page's own <style> block, so it holds no tag itself. --}}
.vf-engine-chip {
    display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 999px;
    background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412;
    font-size: 9.5px; font-weight: 800; line-height: 1.25;
}
.dark .vf-engine-chip { background: rgba(245,158,11,.10); border-color: rgb(255 138 61 / .28); color: #ff8a3d; }
.vf-year-chip {
    display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 999px;
    background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8;
    font-size: 9.5px; font-weight: 800; line-height: 1.25;
}
.dark .vf-year-chip { background: rgba(59,130,246,.10); border-color: rgba(96,165,250,.28); color: #93c5fd; }

/* Buttons */
.vf-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    height: 38px; padding: 0 16px; border-radius: 10px; border: 1px solid #e2e8f0;
    background: #fff; color: #475569; font-size: 12px; font-weight: 800; cursor: pointer;
    text-decoration: none; transition: all .15s ease;
}
.vf-btn:hover { transform: translateY(-1px); }
.vf-btn:focus-visible { outline: 2px solid #ff8a3d; outline-offset: 2px; }
.vf-btn.primary { background: #04041f; color: #ffb27a; border-color: #04041f; }
.vf-btn.primary:hover { background: #070740; }
.vf-btn.gold {
    background: linear-gradient(180deg, #ff8a3d, #e65c00); color: #04041f; border-color: transparent;
    box-shadow: 0 6px 16px -6px rgba(245,158,11,0.5);
}
.vf-btn.gold:hover { filter: brightness(1.05); }
.vf-btn.danger { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
.vf-btn.danger:hover { background: #fee2e2; }
.vf-btn.sm { height: 30px; padding: 0 11px; font-size: 11px; border-radius: 8px; }
.dark .vf-btn { background: #1e293b; border-color: #334155; color: #cbd5e1; }
.dark .vf-btn:hover { background: #334155; }
.dark .vf-btn.primary { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
.dark .vf-btn.primary:hover { background: #e65c00; }
.dark .vf-btn.gold { background: linear-gradient(180deg, #ff8a3d, #e65c00); color: #04041f; }
.dark .vf-btn.danger { background: rgba(239,68,68,0.10); color: #fca5a5; border-color: rgba(239,68,68,0.30); }

/* Inputs */
.vf-inp, .vf-sel {
    width: 100%; height: 38px; padding: 0 12px; font-size: 13px;
    border: 1px solid #e2e8f0; border-radius: 10px;
    background: #f8fafc; color: #0f172a;
}
.vf-inp:focus, .vf-sel:focus {
    outline: none; border-color: #ff8a3d; background: #fff;
    box-shadow: 0 0 0 3px rgb(255 138 61 / 0.25);
}
.vf-inp[aria-invalid="true"], .vf-sel[aria-invalid="true"] { border-color: #f87171; background: #fef2f2; }
.dark .vf-inp, .dark .vf-sel { background: #1e293b; border-color: #334155; color: #f1f5f9; }
.dark .vf-inp:focus, .dark .vf-sel:focus { background: #0f172a; }
.dark .vf-inp[aria-invalid="true"], .dark .vf-sel[aria-invalid="true"] { border-color: #f87171; background: rgba(239,68,68,0.08); }
.vf-tagbox {
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px; min-height: 42px; padding: 6px 9px;
    border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; cursor: text;
}
.vf-tagbox:focus-within { border-color: #ff8a3d; background: #fff; box-shadow: 0 0 0 3px rgb(255 138 61 / .25); }
.vf-tagbox input[data-engine-tag-input] {
    flex: 1 1 130px; min-width: 110px; height: 26px; padding: 0 3px; border: 0; outline: none;
    background: transparent; color: #0f172a; font-size: 12px; box-shadow: none;
}
.vf-tag {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 7px 4px 9px; border-radius: 999px;
    background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; font-size: 10.5px; font-weight: 800;
}
.vf-tag button { color: #c2410c; font-size: 14px; line-height: 1; }
.dark .vf-tagbox { background: #1e293b; border-color: #334155; }
.dark .vf-tagbox:focus-within { background: #0f172a; }
.dark .vf-tagbox input[data-engine-tag-input] { color: #f1f5f9; }
.dark .vf-tag { background: rgba(245,158,11,.10); border-color: rgb(255 138 61 / .28); color: #ff8a3d; }
.dark .vf-tag button { color: #ffb27a; }

.vf-lbl {
    display: block; font-size: 10px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .12em; color: #64748b; margin-bottom: 5px;
}
.dark .vf-lbl { color: #94a3b8; }
