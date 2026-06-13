<x-app-layout>
    <x-slot name="header">{{ __('Orders Management') }}</x-slot>

    <style>
        .orders-page {
            /* Surfaces */
            --surface-page: var(--admin-surface, #111827);
            --surface-card: var(--admin-card, #1e293b);
            --surface-muted: var(--admin-input, #172033);
            --surface-elevated: #243249;
            --surface-hover: #263449;
            /* Borders */
            --border-default: var(--admin-border, #334155);
            --border-soft: var(--admin-border-soft, #263244);
            --border-row: rgba(148, 163, 184, 0.10);
            --border-checkbox: #64748b;
            /* Text */
            --text-primary: var(--admin-text-strong, #f8fafc);
            --text-body: var(--admin-text, #e2e8f0);
            --text-secondary: var(--admin-text-muted, #cbd5e1);
            --text-muted: var(--admin-text-soft, #94a3b8);
            --text-faint: #8796ad;
            --text-disabled: #64748b;
            /* Accent */
            --accent: var(--admin-accent, #06b6d4);
            --accent-hover: var(--admin-accent-hover, #0891b2);
            --accent-text: #67e8f9;
            --accent-soft: rgba(6, 182, 212, 0.10);
            --accent-soft-strong: rgba(6, 182, 212, 0.14);
            --accent-border: rgba(103, 232, 249, 0.24);
            /* Status */
            --status-pending: #f59e0b;
            --status-processing: #8b5cf6;
            --status-shipped: #38bdf8;
            --status-delivered: #22c55e;
            --status-cancelled: #ef4444;
            /* Shadows */
            --shadow-card: 0 1px 0 rgba(255,255,255,0.04) inset, 0 24px 58px -42px rgba(0,0,0,0.72);
            --shadow-hover: 0 1px 0 rgba(255,255,255,0.06) inset, 0 28px 68px -42px rgba(0,0,0,0.78);
            /* Sizes */
            --h-sm: 38px;
            --h-md: 42px;
            --r-sm: 6px;
            --r-md: 8px;
            --r-lg: 12px;
            --r-xl: 14px;

            position: relative;
            background:
                radial-gradient(circle at 12% 0%, rgba(6,182,212,0.09), transparent 34%),
                radial-gradient(circle at 90% 8%, rgba(139,92,246,0.08), transparent 28%),
                transparent;
            color: var(--text-body);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 0;
        }
        .orders-page * { box-sizing: border-box; }
        .orders-page .op-wrap { position: relative; z-index: 1; }

        /* ──────────── Header ──────────── */
        .orders-page .op-hero {
            position: relative;
            overflow: hidden;
            margin-bottom: 18px;
            padding: 20px;
            border-radius: 18px;
            background:
                linear-gradient(135deg, rgba(30,41,59,0.98), rgba(17,32,51,0.96) 58%, rgba(17,24,39,0.98)),
                var(--surface-card);
            border: 1px solid rgba(148,163,184,0.16);
            box-shadow: var(--shadow-card);
        }
        .orders-page .op-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(rgba(148,163,184,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148,163,184,0.035) 1px, transparent 1px);
            background-size: 28px 28px;
            mask-image: linear-gradient(90deg, rgba(0,0,0,0.8), transparent 72%);
            pointer-events: none;
        }
        .orders-page .op-hero::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            right: -120px;
            top: -150px;
            background: radial-gradient(circle, rgba(6,182,212,0.18), transparent 62%);
            pointer-events: none;
        }
        .orders-page .op-hdr {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .orders-page .op-hdr-text { min-width: 0; }
        .orders-page .op-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--accent-text);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .orders-page .op-kicker::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 4px rgba(6,182,212,0.13);
        }
        .orders-page .op-hdr h1 {
            margin: 0;
            font-size: clamp(26px, 3vw, 34px);
            font-weight: 760;
            color: var(--text-primary);
            letter-spacing: 0;
            line-height: 1.2;
        }
        .orders-page .op-hdr .sub {
            margin: 9px 0 0;
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .orders-page .op-hdr .sub b {
            color: var(--status-pending);
            font-weight: 600;
        }
        .orders-page .op-hdr .sub .dot-sep { color: var(--text-disabled); }
        .orders-page .op-hero-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .orders-page .op-live-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: var(--h-md);
            padding: 0 12px;
            border-radius: 999px;
            background: rgba(15,23,42,0.42);
            border: 1px solid rgba(148,163,184,0.16);
            color: var(--text-secondary);
            font-size: 11.5px;
            font-weight: 650;
            white-space: nowrap;
        }
        .orders-page .op-live-chip::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--status-delivered);
        }
        @media (max-width: 640px) {
            .orders-page .op-hero { padding: 16px; border-radius: 14px; }
            .orders-page .op-hdr { flex-direction: column; align-items: stretch; }
            .orders-page .op-hero-actions { justify-content: stretch; }
            .orders-page .op-hero-actions > * { width: 100%; }
            .orders-page .op-live-chip { justify-content: center; }
        }

        /* ──────────── Buttons ──────────── */
        .orders-page .op-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 650;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background .15s ease, border-color .15s ease, color .15s ease, transform .15s ease;
            white-space: nowrap;
            line-height: 1;
        }
        .orders-page .op-btn-sm { height: var(--h-sm); padding: 0 16px; font-size: 12.5px; }
        .orders-page .op-btn-md { height: var(--h-md); padding: 0 18px; font-size: 13px; }
        .orders-page .op-btn-primary {
            background: linear-gradient(180deg, #22d3ee, var(--accent-hover));
            color: #fff;
            border-color: var(--accent-border);
            box-shadow: 0 1px 0 rgba(255,255,255,0.18) inset, 0 14px 24px -20px rgba(6,182,212,0.95);
        }
        .orders-page .op-btn-primary:hover { background: linear-gradient(180deg, #06b6d4, var(--accent-hover)); transform: translateY(-1px); }
        .orders-page .op-btn-primary:active { transform: translateY(0); }
        .orders-page .op-btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border-color: var(--border-soft);
        }
        .orders-page .op-btn-ghost:hover { color: var(--text-body); border-color: var(--border-default); background: var(--surface-hover); }
        .orders-page .op-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
        .orders-page .op-btn:focus-visible,
        .orders-page .op-chip:focus-visible,
        .orders-page .op-icon:focus-visible {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(6,182,212,0.22);
        }

        /* ──────────── Stats ──────────── */
        .orders-page .op-stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        @media (max-width: 1100px) { .orders-page .op-stats { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 700px) { .orders-page .op-stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) {
            .orders-page .op-stats {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                margin: 0 -16px 18px;
                padding: 0 16px;
                gap: 10px;
                scrollbar-width: none;
            }
            .orders-page .op-stats::-webkit-scrollbar { display: none; }
            .orders-page .op-stat { min-width: 150px; scroll-snap-align: start; }
        }
        .orders-page .op-stat {
            background:
                linear-gradient(180deg, rgba(36,50,73,0.94), rgba(30,41,59,0.96)),
                var(--surface-card);
            border: 1px solid rgba(148,163,184,0.14);
            border-radius: 14px;
            padding: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-card);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .orders-page .op-stat:hover {
            transform: translateY(-1px);
            border-color: rgba(148,163,184,0.26);
            box-shadow: var(--shadow-hover);
        }
        .orders-page .op-stat::before {
            content: '';
            position: absolute;
            top: 0; left: 12px; right: 12px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--stat-a, var(--border-checkbox)), transparent);
            opacity: 0.9;
        }
        .orders-page .op-stat::after {
            content: '';
            position: absolute;
            right: -34px;
            bottom: -42px;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--stat-a, #94a3b8) 18%, transparent);
            opacity: .45;
        }
        .orders-page .op-stat .l {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
            font-weight: 600;
        }
        .orders-page .op-stat .v {
            position: relative;
            z-index: 1;
            font-size: 30px;
            font-weight: 760;
            margin-top: 8px;
            line-height: 1;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .orders-page .op-stat.tot   { --stat-a: var(--text-secondary); }
        .orders-page .op-stat.warn  { --stat-a: var(--status-pending); }
        .orders-page .op-stat.idx   { --stat-a: var(--status-processing); }
        .orders-page .op-stat.info  { --stat-a: var(--status-shipped); }
        .orders-page .op-stat.ok    { --stat-a: var(--status-delivered); }
        .orders-page .op-stat.err   { --stat-a: var(--status-cancelled); }

        /* ──────────── Attention chips ──────────── */
        .orders-page .op-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .orders-page .op-chip {
            background: rgba(30,41,59,0.76);
            border: 1px solid var(--border-soft);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 500;
            color: var(--text-muted);
            transition: background .15s ease, color .15s ease, border-color .15s ease;
            text-decoration: none;
            line-height: 1.2;
        }
        .orders-page .op-chip:hover {
            background: var(--surface-hover);
            border-color: var(--border-default);
            color: var(--text-secondary);
        }
        .orders-page .op-chip.on {
            background: var(--accent-soft-strong);
            color: var(--accent-text);
            border-color: var(--accent-border);
            font-weight: 600;
        }

        /* ──────────── Card shell ──────────── */
        .orders-page .op-card {
            background: linear-gradient(180deg, rgba(30,41,59,0.98), rgba(30,41,59,0.94));
            border: 1px solid rgba(148,163,184,0.16);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }

        /* ──────────── Filter form ──────────── */
        .orders-page .op-filter-form {
            background: linear-gradient(180deg, rgba(23,32,51,0.98), rgba(17,32,51,0.94));
            border-bottom: 1px solid rgba(148,163,184,0.12);
        }
        .orders-page .op-filter {
            padding: 18px;
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 12px;
        }
        .orders-page .op-filter > .f-search    { grid-column: span 4; }
        .orders-page .op-filter > .f-status    { grid-column: span 2; }
        .orders-page .op-filter > .f-date      { grid-column: span 2; }
        .orders-page .op-filter > .f-assoc     { grid-column: span 2; }
        @media (max-width: 1100px) {
            .orders-page .op-filter > .f-search { grid-column: span 12; }
            .orders-page .op-filter > .f-status,
            .orders-page .op-filter > .f-assoc  { grid-column: span 6; }
            .orders-page .op-filter > .f-date   { grid-column: span 6; }
        }
        @media (max-width: 640px) {
            .orders-page .op-filter > * { grid-column: span 12 !important; }
        }
        .orders-page .f-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 7px;
        }
        .orders-page .op-input,
        .orders-page .op-select {
            background: rgba(15,23,42,0.62);
            border: 1px solid rgba(148,163,184,0.18);
            color: var(--text-body);
            height: 44px;
            padding: 0 14px;
            border-radius: var(--r-md);
            font-size: 13.5px;
            font-family: inherit;
            width: 100%;
            color-scheme: dark;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .orders-page .op-input::placeholder { color: var(--text-disabled); }
        .orders-page .op-input:focus,
        .orders-page .op-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(6,182,212,0.22);
        }
        .orders-page .op-input::-webkit-calendar-picker-indicator {
            filter: invert(0.7) brightness(1.2);
            cursor: pointer;
            opacity: 0.7;
        }
        .orders-page .op-input::-webkit-calendar-picker-indicator:hover { opacity: 1; }
        .orders-page .op-select {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 38px;
        }
        [dir='rtl'] .orders-page .op-select {
            background-position: left 14px center;
            padding-right: 14px;
            padding-left: 38px;
        }
        .orders-page .op-dates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .orders-page .op-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 0 18px 18px;
            border-bottom: 1px solid rgba(148,163,184,0.12);
            background: rgba(17,32,51,0.94);
        }

        /* ──────────── Bulk action bar ──────────── */
        .orders-page .op-bulk {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            background: var(--accent-soft-strong);
            border-bottom: 1px solid var(--accent-border);
            padding: 14px 16px;
        }
        .orders-page .op-bulk .count {
            display: inline-flex;
            align-items: center;
            min-height: 32px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(6,182,212,0.10);
            border: 1px solid rgba(6,182,212,0.18);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--accent-text);
        }
        .orders-page .op-bulk form { display: flex; align-items: end; gap: 12px; margin-left: auto; }
        [dir='rtl'] .orders-page .op-bulk form { margin-left: 0; margin-right: auto; }
        .orders-page .op-bulk-field {
            display: grid;
            gap: 5px;
            min-width: 260px;
        }
        .orders-page .op-bulk-label {
            color: var(--accent-text);
            font-size: 10.5px;
            font-weight: 750;
            letter-spacing: .09em;
            line-height: 1;
            text-transform: uppercase;
        }
        .orders-page .op-bulk select {
            background-color: #ffffff;
            border: 1px solid rgba(6,182,212,0.35);
            color: #0f172a;
            height: 46px;
            width: 100%;
            min-width: 260px;
            padding: 0 42px 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 650;
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%230891b2' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: right 16px center;
            box-shadow: 0 1px 0 rgba(255,255,255,0.80) inset, 0 10px 24px -22px rgba(15,23,42,0.42);
            color-scheme: light;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        [dir='rtl'] .orders-page .op-bulk select {
            padding: 0 16px 0 42px;
            background-position: left 16px center;
        }
        .orders-page .op-bulk select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(6,182,212,0.20);
        }

        /* ──────────── Table ──────────── */
        .orders-page .op-table-wrap { overflow-x: auto; scrollbar-gutter: stable; }
        .orders-page .op-table-wrap::-webkit-scrollbar { height: 8px; }
        .orders-page .op-table-wrap::-webkit-scrollbar-thumb {
            background: var(--border-default);
            border-radius: 4px;
        }
        .orders-page .op-tbl {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            font-size: 12px;
        }
        .orders-page .op-tbl thead th {
            background: #172033;
            color: var(--text-muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border-default);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        [dir='rtl'] .orders-page .op-tbl thead th { text-align: right; }
        .orders-page .op-tbl thead th.ralign,
        .orders-page .op-tbl tbody td.ralign { text-align: right; }
        [dir='rtl'] .orders-page .op-tbl thead th.ralign,
        [dir='rtl'] .orders-page .op-tbl tbody td.ralign { text-align: left; }

        .orders-page .op-tbl tbody tr {
            background: #1e293b;
            border-bottom: 1px solid var(--border-row);
            transition: background .12s ease;
        }
        .orders-page .op-tbl tbody tr:nth-child(even) { background: #202c40; }
        .orders-page .op-tbl tbody tr:last-child { border-bottom: none; }
        .orders-page .op-tbl tbody tr:hover { background: var(--surface-hover); }
        .orders-page .op-tbl td { padding: 14px 16px; vertical-align: middle; }
        .orders-page .op-tbl .num {
            font-weight: 600;
            color: var(--text-primary);
            display: block;
            font-size: 12.5px;
            margin-bottom: 2px;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .orders-page .op-tbl .id  { font-family: 'JetBrains Mono', ui-monospace, monospace; color: var(--text-muted); font-size: 10px; }
        .orders-page .op-tbl .who { color: var(--text-body); font-weight: 500; font-size: 12.5px; }
        .orders-page .op-tbl .em  { color: var(--text-faint); font-size: 10.5px; margin-top: 1px; }
        .orders-page .op-tbl .ttl { font-weight: 700; color: var(--text-primary); font-variant-numeric: tabular-nums; font-size: 12.5px; }
        .orders-page .op-tbl .ttl .cy { color: var(--text-faint); font-weight: 400; font-size: 10px; margin-left: 3px; }
        [dir='rtl'] .orders-page .op-tbl .ttl .cy { margin-left: 0; margin-right: 3px; }
        .orders-page .op-tbl .meth { color: var(--text-disabled); font-size: 9.5px; margin-top: 3px; font-family: 'JetBrains Mono', monospace; }
        .orders-page .op-tbl .items-cnt { color: var(--text-secondary); font-variant-numeric: tabular-nums; }
        .orders-page .op-tbl input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: var(--accent);
            border-radius: 3px;
            background: var(--surface-muted);
            border: 1.5px solid var(--border-checkbox);
            cursor: pointer;
        }
        .orders-page .op-tbl .date-d { color: var(--text-secondary); font-size: 11.5px; font-variant-numeric: tabular-nums; }
        .orders-page .op-tbl .date-t { color: var(--text-faint); font-size: 9.5px; margin-top: 2px; font-variant-numeric: tabular-nums; }

        /* Empty state */
        .orders-page .op-empty {
            padding: 56px 16px;
            text-align: center;
        }
        .orders-page .op-empty .ico {
            width: 44px; height: 44px;
            margin: 0 auto 12px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border-soft);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted);
        }
        .orders-page .op-empty .ico svg { width: 22px; height: 22px; }
        .orders-page .op-empty .title { color: var(--text-primary); font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .orders-page .op-empty .help  { color: var(--text-muted); font-size: 12px; }

        /* ──────────── Status pills ──────────── */
        .orders-page .op-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 600;
            border: 1px solid;
            line-height: 1.2;
            letter-spacing: .01em;
            white-space: nowrap;
        }
        .orders-page .op-pill::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.9;
            flex-shrink: 0;
        }
        .orders-page .op-pill.pending    { background: rgba(245,158,11,0.10); color: var(--status-pending); border-color: rgba(245,158,11,0.26); }
        .orders-page .op-pill.processing { background: rgba(139,92,246,0.10); color: #c4b5fd; border-color: rgba(139,92,246,0.26); }
        .orders-page .op-pill.shipped    { background: rgba(56,189,248,0.10); color: var(--status-shipped); border-color: rgba(56,189,248,0.26); }
        .orders-page .op-pill.delivered  { background: rgba(34,197,94,0.10); color: var(--status-delivered); border-color: rgba(34,197,94,0.26); }
        .orders-page .op-pill.cancelled  { background: rgba(239,68,68,0.10); color: #f87171; border-color: rgba(239,68,68,0.26); }
        .orders-page .op-pill.paid       { background: rgba(34,197,94,0.10); color: var(--status-delivered); border-color: rgba(34,197,94,0.26); }
        .orders-page .op-pill.pending_payment,
        .orders-page .op-pill.pending-payment { background: rgba(56,189,248,0.10); color: var(--status-shipped); border-color: rgba(56,189,248,0.26); }
        .orders-page .op-pill.failed     { background: rgba(239,68,68,0.10); color: #f87171; border-color: rgba(239,68,68,0.26); }
        .orders-page .op-pill.refunded   { background: rgba(148,163,184,0.10); color: var(--text-secondary); border-color: rgba(203,213,225,0.20); }

        /* Inline tag (e.g. user/dealer) - flat, no dot */
        .orders-page .op-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            border: 1px solid;
            margin-top: 6px;
        }
        .orders-page .op-tag.user   { background: rgba(56,189,248,0.10); color: var(--status-shipped); border-color: rgba(56,189,248,0.26); }
        .orders-page .op-tag.dealer { background: rgba(139,92,246,0.10); color: #c4b5fd; border-color: rgba(139,92,246,0.26); }

        /* Row alerts (cancellation/return) */
        .orders-page .op-alert {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(239,68,68,0.12);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.26);
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .orders-page .op-alert.warn {
            background: rgba(245,158,11,0.12);
            color: #f59e0b;
            border-color: rgba(245,158,11,0.26);
        }

        .orders-page .op-row-selected { background: var(--accent-soft) !important; }

        /* ──────────── Action icons + kebab ──────────── */
        .orders-page .op-acts {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            align-items: center;
        }
        [dir='rtl'] .orders-page .op-acts { justify-content: flex-start; }
        .orders-page .op-icon {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--r-sm);
            background: var(--surface-elevated);
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
            transition: color .12s ease, border-color .12s ease, background .12s ease, transform .12s ease;
            text-decoration: none;
            cursor: pointer;
        }
        .orders-page .op-icon:hover {
            color: var(--accent-text);
            border-color: var(--accent-border);
            background: var(--surface-hover);
            transform: translateY(-1px);
        }
        .orders-page .op-icon svg { width: 14px; height: 14px; }

        /* Kebab dropdown (teleported to <body>) */
        .op-menu {
            position: fixed;
            width: min(270px, calc(100vw - 16px));
            background: var(--admin-card, #1e293b);
            border: 1px solid var(--admin-border-soft, #263244);
            border-radius: 10px;
            box-shadow: 0 24px 48px -28px rgba(0,0,0,0.75), 0 1px 0 rgba(255,255,255,0.04) inset;
            padding: 6px;
            z-index: 60;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--admin-text, #f8fafc);
        }
        .op-menu .head {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--admin-text-soft, #94a3b8);
            padding: 8px 10px 4px;
            font-weight: 700;
        }
        .op-menu form { display: flex; align-items: center; gap: 8px; padding: 6px 8px 10px; }
        .op-menu select {
            flex: 1 1 auto;
            min-width: 0;
            background: var(--admin-surface, #111827);
            border: 1px solid var(--admin-border, #334155);
            color: var(--admin-text, #f8fafc);
            height: 38px;
            padding: 0 10px;
            border-radius: 8px;
            font-size: 12.5px;
            color-scheme: dark;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .op-menu select:focus {
            outline: none;
            border-color: var(--admin-accent, #06b6d4);
            box-shadow: 0 0 0 2px rgba(6,182,212,0.20);
        }
        .op-menu button[type="submit"] {
            background: var(--admin-accent, #06b6d4);
            color: white;
            height: 38px;
            padding: 0 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1px solid rgba(103,232,249,0.24);
            cursor: pointer;
            transition: background .15s ease;
        }
        .op-menu button[type="submit"]:hover { background: var(--admin-accent-hover, #0891b2); }
        .op-menu button[type="submit"]:focus-visible,
        .op-menu .danger:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px rgba(6,182,212,0.20);
        }
        .op-menu hr { border: 0; border-top: 1px solid var(--admin-border-soft, #263244); margin: 4px 0; }
        .op-menu .danger {
            display: block; width: 100%;
            text-align: left;
            padding: 10px 12px;
            border-radius: 8px;
            background: transparent;
            color: #fca5a5;
            font-size: 12.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background .15s ease;
        }
        [dir='rtl'] .op-menu .danger { text-align: right; }
        .op-menu .danger:hover { background: rgba(239,68,68,0.10); }
        .op-invoice-menu {
            width: min(218px, calc(100vw - 16px));
            padding: 8px;
        }
        .op-invoice-menu .invoice-lang {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--admin-text, #f8fafc);
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            transition: background .15s ease, color .15s ease;
        }
        .op-invoice-menu .invoice-lang:hover {
            background: rgba(6,182,212,0.12);
            color: #67e8f9;
        }
        .op-invoice-menu .invoice-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 24px;
            border-radius: 999px;
            background: rgba(6,182,212,0.14);
            color: #67e8f9;
            font-size: 11px;
            letter-spacing: .04em;
        }

        /* ──────────── Pagination ──────────── */
        .orders-page .op-pag {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-top: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-size: 11.5px;
            background: var(--surface-card);
            gap: 12px;
            flex-wrap: wrap;
        }
        .orders-page .op-pag nav { display: flex; }
        .orders-page .op-pag svg { width: 14px; height: 14px; }
        .orders-page .op-pag .pagination,
        .orders-page .op-pag ul {
            display: flex; gap: 6px; list-style: none; margin: 0; padding: 0;
        }
        .orders-page .op-pag a,
        .orders-page .op-pag span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px;
            padding: 0 10px;
            border-radius: var(--r-sm);
            background: var(--surface-elevated);
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            transition: color .12s ease, border-color .12s ease, background .12s ease;
        }
        .orders-page .op-pag a:hover { color: var(--text-body); border-color: var(--border-default); background: var(--surface-hover); }
        .orders-page .op-pag .active span,
        .orders-page .op-pag span[aria-current="page"] {
            background: var(--accent);
            color: white;
            border-color: rgba(255,255,255,0.15);
        }
        .orders-page .op-pag .disabled span,
        .orders-page .op-pag span[aria-disabled="true"] {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* ──────────── Flash messages ──────────── */
        .orders-page .op-flash {
            margin-bottom: 16px;
            border-radius: var(--r-md);
            padding: 10px 14px;
            font-size: 12.5px;
            font-weight: 500;
            border: 1px solid;
        }
        .orders-page .op-flash.ok {
            background: rgba(34,197,94,0.10);
            border-color: rgba(34,197,94,0.26);
            color: #86efac;
        }
        .orders-page .op-flash.err {
            background: rgba(239,68,68,0.10);
            border-color: rgba(239,68,68,0.26);
            color: #fca5a5;
        }

        /* Light operations skin */
        .orders-page {
            --surface-page: #f6f8fb;
            --surface-card: #ffffff;
            --surface-muted: #f8fafc;
            --surface-elevated: #eef3f8;
            --surface-hover: #f1f5f9;
            --border-default: #d7e0ea;
            --border-soft: #e2e8f0;
            --border-row: #e8eef5;
            --border-checkbox: #94a3b8;
            --text-primary: #0f172a;
            --text-body: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-faint: #64748b;
            --text-disabled: #94a3b8;
            --accent-text: #0891b2;
            --accent-soft: rgba(6,182,212,0.09);
            --accent-soft-strong: rgba(6,182,212,0.13);
            --accent-border: rgba(6,182,212,0.24);
            --shadow-card: 0 1px 2px rgba(15,23,42,0.04), 0 18px 42px -34px rgba(15,23,42,0.38);
            --shadow-hover: 0 2px 8px rgba(15,23,42,0.06), 0 22px 54px -34px rgba(15,23,42,0.42);
            background:
                radial-gradient(circle at 4% 0%, rgba(6,182,212,0.10), transparent 30%),
                radial-gradient(circle at 94% 8%, rgba(139,92,246,0.08), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #eef4f8 100%);
        }
        .orders-page .op-hero {
            background:
                linear-gradient(135deg, rgba(255,255,255,0.98), rgba(239,248,252,0.96) 58%, rgba(246,248,255,0.98)),
                #fff;
            border-color: rgba(148,163,184,0.24);
        }
        .orders-page .op-hero::before {
            background:
                linear-gradient(rgba(15,23,42,0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15,23,42,0.028) 1px, transparent 1px);
        }
        .orders-page .op-hero::after { background: radial-gradient(circle, rgba(6,182,212,0.14), transparent 62%); }
        .orders-page .op-live-chip {
            background: rgba(255,255,255,0.72);
            border-color: rgba(148,163,184,0.26);
            color: #334155;
        }
        .orders-page .op-btn-ghost {
            background: #fff;
            color: #475569;
            border-color: #dbe3ed;
        }
        .orders-page .op-btn-ghost:hover {
            color: #0f172a;
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .orders-page .op-stats {
            gap: 0;
            padding: 8px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(148,163,184,0.24);
            border-radius: 18px;
            box-shadow: var(--shadow-card);
        }
        .orders-page .op-stat {
            background: transparent;
            border: 0;
            border-radius: 12px;
            box-shadow: none;
            min-height: 92px;
        }
        .orders-page .op-stat + .op-stat { border-left: 1px solid #e8eef5; }
        [dir='rtl'] .orders-page .op-stat + .op-stat {
            border-left: 0;
            border-right: 1px solid #e8eef5;
        }
        .orders-page .op-stat:hover {
            background: #f8fafc;
            box-shadow: none;
        }
        .orders-page .op-stat::before {
            left: 16px;
            right: auto;
            width: 34px;
            background: var(--stat-a, #94a3b8);
            opacity: .85;
        }
        [dir='rtl'] .orders-page .op-stat::before { left: auto; right: 16px; }
        .orders-page .op-stat::after {
            width: 54px;
            height: 54px;
            right: 12px;
            bottom: 12px;
            opacity: .16;
        }
        [dir='rtl'] .orders-page .op-stat::after { right: auto; left: 12px; }
        .orders-page .op-stat .l { color: #64748b; }
        .orders-page .op-stat .v { color: #0f172a; font-size: 28px; }
        .orders-page .op-chip {
            background: rgba(255,255,255,0.92);
            border-color: rgba(148,163,184,0.28);
            color: #475569;
        }
        .orders-page .op-chip:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .orders-page .op-chip.on {
            background: #cffafe;
            color: #0e7490;
            border-color: #a5f3fc;
        }
        .orders-page .op-card {
            background: #ffffff;
            border-color: rgba(148,163,184,0.24);
            box-shadow: var(--shadow-card);
        }
        .orders-page .op-filter-form {
            background: #f8fafc;
            border-bottom-color: #e2e8f0;
        }
        .orders-page .f-label {
            color: #64748b;
        }
        .orders-page .op-input,
        .orders-page .op-select,
        .orders-page .op-bulk select {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            color-scheme: light;
        }
        .orders-page .op-bulk select {
            border-color: rgba(6,182,212,0.35);
        }
        .orders-page .op-input::placeholder { color: #94a3b8; }
        .orders-page .op-actions {
            background: #f8fafc;
            border-bottom-color: #e2e8f0;
        }
        .orders-page .op-table-wrap {
            background: #fff;
        }
        .orders-page .op-tbl thead th {
            background: #f8fafc;
            color: #64748b;
            border-bottom-color: #dbe3ed;
        }
        .orders-page .op-tbl tbody tr,
        .orders-page .op-tbl tbody tr:nth-child(even) {
            background: #ffffff;
            border-bottom-color: #edf2f7;
        }
        .orders-page .op-tbl tbody tr:hover { background: #f8fafc; }
        .orders-page .op-tbl td { color: #1e293b; }
        .orders-page .op-tbl .num,
        .orders-page .op-tbl .ttl,
        .orders-page .op-tbl .who { color: #0f172a; }
        .orders-page .op-tbl .id,
        .orders-page .op-tbl .em,
        .orders-page .op-tbl .date-t,
        .orders-page .op-tbl .meth { color: #64748b; }
        .orders-page .op-tbl .date-d,
        .orders-page .op-tbl .items-cnt,
        .orders-page .op-tbl .ttl .cy { color: #475569; }
        .orders-page .op-tbl input[type="checkbox"] {
            background: #fff;
            border-color: #94a3b8;
        }
        .orders-page .op-icon {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #64748b;
        }
        .orders-page .op-icon:hover {
            background: #e0f7fb;
            border-color: #a5f3fc;
            color: #0891b2;
        }
        .orders-page .op-pag {
            background: #ffffff;
            border-top-color: #e2e8f0;
            color: #64748b;
        }
        .orders-page .op-pag a,
        .orders-page .op-pag span {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #475569;
        }
        .orders-page .op-pag a:hover {
            background: #eef2f7;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .orders-page .op-empty .ico {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }
        .orders-page .op-empty .title { color: #0f172a; }
        .orders-page .op-empty .help { color: #64748b; }
        .op-menu {
            background: #ffffff;
            border-color: #dbe3ed;
            color: #0f172a;
            box-shadow: 0 24px 48px -30px rgba(15,23,42,0.32), 0 1px 0 rgba(255,255,255,0.85) inset;
        }
        .op-menu .head { color: #64748b; }
        .op-invoice-menu .invoice-lang { color: #0f172a; }
        .op-invoice-menu .invoice-lang:hover {
            background: #ecfeff;
            color: #0891b2;
        }
        .op-invoice-menu .invoice-code {
            background: #cffafe;
            color: #0e7490;
        }
        .op-menu select {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            color-scheme: light;
        }
        .op-menu hr { border-top-color: #e2e8f0; }
        .op-menu .danger:hover { background: #fef2f2; }

        .dark .orders-page {
            --surface-page: var(--admin-surface, #111827);
            --surface-card: var(--admin-card, #1e293b);
            --surface-muted: var(--admin-input, #172033);
            --surface-elevated: #243249;
            --surface-hover: #263449;
            --border-default: var(--admin-border, #334155);
            --border-soft: var(--admin-border-soft, #263244);
            --border-row: rgba(148, 163, 184, 0.10);
            --border-checkbox: #64748b;
            --text-primary: var(--admin-text-strong, #f8fafc);
            --text-body: var(--admin-text, #e2e8f0);
            --text-secondary: var(--admin-text-muted, #cbd5e1);
            --text-muted: var(--admin-text-soft, #94a3b8);
            --text-faint: #8796ad;
            --text-disabled: #64748b;
            --accent-text: #67e8f9;
            --accent-soft: rgba(6, 182, 212, 0.10);
            --accent-soft-strong: rgba(6, 182, 212, 0.14);
            --accent-border: rgba(103, 232, 249, 0.24);
            --shadow-card: 0 1px 0 rgba(255,255,255,0.04) inset, 0 24px 58px -42px rgba(0,0,0,0.72);
            --shadow-hover: 0 1px 0 rgba(255,255,255,0.06) inset, 0 28px 68px -42px rgba(0,0,0,0.78);
            background:
                radial-gradient(circle at 12% 0%, rgba(6,182,212,0.09), transparent 34%),
                radial-gradient(circle at 90% 8%, rgba(139,92,246,0.08), transparent 28%),
                transparent;
        }
        .dark .orders-page .op-hero {
            background:
                linear-gradient(135deg, rgba(30,41,59,0.98), rgba(17,32,51,0.96) 58%, rgba(17,24,39,0.98)),
                var(--surface-card);
            border-color: rgba(148,163,184,0.16);
        }
        .dark .orders-page .op-hero::before {
            background:
                linear-gradient(rgba(148,163,184,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148,163,184,0.035) 1px, transparent 1px);
        }
        .dark .orders-page .op-hero::after { background: radial-gradient(circle, rgba(6,182,212,0.18), transparent 62%); }
        .dark .orders-page .op-live-chip {
            background: rgba(15,23,42,0.42);
            border-color: rgba(148,163,184,0.16);
            color: var(--text-secondary);
        }
        .dark .orders-page .op-btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border-color: var(--border-soft);
        }
        .dark .orders-page .op-btn-ghost:hover {
            color: var(--text-body);
            border-color: var(--border-default);
            background: var(--surface-hover);
        }
        .dark .orders-page .op-stats {
            gap: 12px;
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }
        .dark .orders-page .op-stat {
            background:
                linear-gradient(180deg, rgba(36,50,73,0.94), rgba(30,41,59,0.96)),
                var(--surface-card);
            border: 1px solid rgba(148,163,184,0.14);
            border-radius: 14px;
            box-shadow: var(--shadow-card);
            min-height: auto;
        }
        .dark .orders-page .op-stat + .op-stat { border-left: 1px solid rgba(148,163,184,0.14); }
        [dir='rtl'] .dark .orders-page .op-stat + .op-stat { border-right: 1px solid rgba(148,163,184,0.14); }
        .dark .orders-page .op-stat:hover {
            background:
                linear-gradient(180deg, rgba(38,52,73,0.98), rgba(31,42,62,0.98)),
                var(--surface-card);
            border-color: rgba(148,163,184,0.26);
            box-shadow: var(--shadow-hover);
        }
        .dark .orders-page .op-stat::before {
            left: 12px;
            right: 12px;
            width: auto;
            background: linear-gradient(90deg, transparent, var(--stat-a, var(--border-checkbox)), transparent);
            opacity: .9;
        }
        [dir='rtl'] .dark .orders-page .op-stat::before { left: 12px; right: 12px; }
        .dark .orders-page .op-stat::after {
            width: 96px;
            height: 96px;
            right: -34px;
            bottom: -42px;
            opacity: .45;
        }
        [dir='rtl'] .dark .orders-page .op-stat::after { right: auto; left: -34px; }
        .dark .orders-page .op-stat .l { color: var(--text-muted); }
        .dark .orders-page .op-stat .v { color: var(--text-primary); font-size: 30px; }
        .dark .orders-page .op-chip {
            background: rgba(30,41,59,0.76);
            border-color: var(--border-soft);
            color: var(--text-muted);
        }
        .dark .orders-page .op-chip:hover {
            background: var(--surface-hover);
            border-color: var(--border-default);
            color: var(--text-secondary);
        }
        .dark .orders-page .op-chip.on {
            background: var(--accent-soft-strong);
            color: var(--accent-text);
            border-color: var(--accent-border);
        }
        .dark .orders-page .op-card {
            background: linear-gradient(180deg, rgba(30,41,59,0.98), rgba(30,41,59,0.94));
            border-color: rgba(148,163,184,0.16);
            box-shadow: var(--shadow-card);
        }
        .dark .orders-page .op-filter-form,
        .dark .orders-page .op-actions {
            background: linear-gradient(180deg, rgba(23,32,51,0.98), rgba(17,32,51,0.94));
            border-bottom-color: rgba(148,163,184,0.12);
        }
        .dark .orders-page .f-label { color: var(--text-muted); }
        .dark .orders-page .op-input,
        .dark .orders-page .op-select,
        .dark .orders-page .op-bulk select {
            background-color: rgba(15,23,42,0.62);
            border-color: rgba(148,163,184,0.18);
            color: var(--text-body);
            color-scheme: dark;
        }
        .dark .orders-page .op-bulk select {
            background-color: #172033;
            border-color: rgba(103,232,249,0.28);
            color: #f8fafc;
            box-shadow: 0 1px 0 rgba(255,255,255,0.04) inset;
        }
        .dark .orders-page .op-input::placeholder { color: var(--text-disabled); }
        .dark .orders-page .op-table-wrap { background: var(--surface-card); }
        .dark .orders-page .op-tbl thead th {
            background: #172033;
            color: var(--text-muted);
            border-bottom-color: var(--border-default);
        }
        .dark .orders-page .op-tbl tbody tr {
            background: #1e293b;
            border-bottom-color: var(--border-row);
        }
        .dark .orders-page .op-tbl tbody tr:nth-child(even) { background: #202c40; }
        .dark .orders-page .op-tbl tbody tr:hover { background: var(--surface-hover); }
        .dark .orders-page .op-tbl td { color: var(--text-body); }
        .dark .orders-page .op-tbl .num,
        .dark .orders-page .op-tbl .ttl,
        .dark .orders-page .op-tbl .who { color: var(--text-primary); }
        .dark .orders-page .op-tbl .id,
        .dark .orders-page .op-tbl .em,
        .dark .orders-page .op-tbl .date-t,
        .dark .orders-page .op-tbl .meth { color: var(--text-faint); }
        .dark .orders-page .op-tbl .date-d,
        .dark .orders-page .op-tbl .items-cnt,
        .dark .orders-page .op-tbl .ttl .cy { color: var(--text-secondary); }
        .dark .orders-page .op-tbl input[type="checkbox"] {
            background: var(--surface-muted);
            border-color: var(--border-checkbox);
        }
        .dark .orders-page .op-icon {
            background: var(--surface-elevated);
            border-color: var(--border-soft);
            color: var(--text-muted);
        }
        .dark .orders-page .op-icon:hover {
            background: var(--surface-hover);
            border-color: var(--accent-border);
            color: var(--accent-text);
        }
        .dark .orders-page .op-pag {
            background: var(--surface-card);
            border-top-color: var(--border-soft);
            color: var(--text-muted);
        }
        .dark .orders-page .op-pag a,
        .dark .orders-page .op-pag span {
            background: var(--surface-elevated);
            border-color: var(--border-soft);
            color: var(--text-muted);
        }
        .dark .orders-page .op-pag a:hover {
            background: var(--surface-hover);
            border-color: var(--border-default);
            color: var(--text-body);
        }
        .dark .orders-page .op-empty .ico {
            background: var(--surface-muted);
            border-color: var(--border-soft);
            color: var(--text-muted);
        }
        .dark .orders-page .op-empty .title { color: var(--text-primary); }
        .dark .orders-page .op-empty .help { color: var(--text-muted); }
        .dark .op-menu {
            background: var(--admin-card, #1e293b);
            border-color: var(--admin-border-soft, #263244);
            color: var(--admin-text, #f8fafc);
            box-shadow: 0 24px 48px -28px rgba(0,0,0,0.75), 0 1px 0 rgba(255,255,255,0.04) inset;
        }
        .dark .op-menu .head { color: var(--admin-text-soft, #94a3b8); }
        .dark .op-invoice-menu .invoice-lang { color: var(--admin-text, #f8fafc); }
        .dark .op-invoice-menu .invoice-lang:hover {
            background: rgba(6,182,212,0.12);
            color: #67e8f9;
        }
        .dark .op-invoice-menu .invoice-code {
            background: rgba(6,182,212,0.14);
            color: #67e8f9;
        }
        .dark .op-menu select {
            background: var(--admin-surface, #111827);
            border-color: var(--admin-border, #334155);
            color: var(--admin-text, #f8fafc);
            color-scheme: dark;
        }
        .dark .op-menu hr { border-top-color: var(--admin-border-soft, #263244); }
        .dark .op-menu .danger:hover { background: rgba(239,68,68,0.10); }

        /* ──────────── Responsive column hiding ──────────── */
        @media (max-width: 1023px) {
            .orders-page .col-items,
            .orders-page .op-tbl .meth { display: none !important; }
        }
        @media (max-width: 640px) {
            .orders-page .op-stats {
                padding: 0 16px;
                background: transparent;
                border: 0;
                box-shadow: none;
            }
            .orders-page .op-stat {
                background: #fff;
                border: 1px solid rgba(148,163,184,0.24);
                box-shadow: var(--shadow-card);
            }
            .orders-page .op-stat + .op-stat { border-left: 1px solid rgba(148,163,184,0.24); }
            .dark .orders-page .op-stat {
                background:
                    linear-gradient(180deg, rgba(36,50,73,0.94), rgba(30,41,59,0.96)),
                    var(--surface-card);
                border-color: rgba(148,163,184,0.14);
            }
            .dark .orders-page .op-stat + .op-stat { border-left-color: rgba(148,163,184,0.14); }
            .orders-page .op-bulk {
                align-items: stretch;
            }
            .orders-page .op-bulk form {
                width: 100%;
                margin-left: 0;
                flex-wrap: wrap;
            }
            .orders-page .op-bulk-field {
                min-width: 100%;
                width: 100%;
            }
            .orders-page .op-bulk select {
                min-width: 100%;
                width: 100%;
            }
            .orders-page .op-bulk .op-btn {
                flex: 1 1 0;
            }
            .orders-page .col-date { display: none !important; }
            .orders-page .op-tbl td { padding: 12px 12px; }
        }
    </style>

    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $hasActiveFilters = request()->hasAny(['search', 'status', 'association', 'from', 'to']);
    @endphp

    <div class="orders-page py-8">
        <div class="op-wrap max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="op-flash ok">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="op-flash err">{{ session('error') }}</div>
            @endif

            <div class="op-hero">
                <div class="op-hdr">
                    <div class="op-hdr-text">
                        <div class="op-kicker">{{ __('Order Operations') }}</div>
                        <h1>{{ __('Orders Management') }}</h1>
                        <p class="sub">
                            {{ __(':total total orders', ['total' => number_format($stats['total'] ?? 0)]) }}
                            @if(($stats['pending'] ?? 0) > 0)
                                <span class="dot-sep">&middot;</span>
                                <b>{{ __(':n need attention', ['n' => $stats['pending']]) }}</b>
                            @endif
                        </p>
                    </div>
                    <div class="op-hero-actions">
                        <span class="op-live-chip">{{ __('Live order queue') }}</span>
                        <a class="op-btn op-btn-primary op-btn-md" href="{{ route('admin.orders.export-excel', array_filter([
                                'search' => request('search'),
                                'from' => request('from'),
                                'to' => request('to'),
                                'status' => request('status'),
                                'association' => request('association'),
                                'attention' => request('attention'),
                            ], fn ($v) => $v !== null && $v !== '')) }}">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                            {{ __('Export Excel (.xlsx)') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats cards --}}
            <div class="op-stats">
                <div class="op-stat tot"><div class="l">{{ __('Total') }}</div><div class="v">{{ number_format($stats['total'] ?? 0) }}</div></div>
                <div class="op-stat warn"><div class="l">{{ __('Pending') }}</div><div class="v">{{ number_format($stats['pending'] ?? 0) }}</div></div>
                <div class="op-stat idx"><div class="l">{{ __('Processing') }}</div><div class="v">{{ number_format($stats['processing'] ?? 0) }}</div></div>
                <div class="op-stat info"><div class="l">{{ __('Shipped') }}</div><div class="v">{{ number_format($stats['shipped'] ?? 0) }}</div></div>
                <div class="op-stat ok"><div class="l">{{ __('Delivered') }}</div><div class="v">{{ number_format($stats['delivered'] ?? 0) }}</div></div>
                <div class="op-stat err"><div class="l">{{ __('Cancelled') }}</div><div class="v">{{ number_format($stats['cancelled'] ?? 0) }}</div></div>
            </div>

            {{-- Attention chips --}}
            @php
                $currentAttention = $attention ?? '';
                $attentionChips = [
                    ''                       => __('All'),
                    'today_pending'          => __('Today pending orders'),
                    'needs_shipping'         => __('Needs shipping'),
                    'cancellation_requests'  => __('Cancellation requests'),
                    'open_returns'           => __('Return requests'),
                ];
            @endphp
            <div class="op-chips">
                @foreach($attentionChips as $value => $label)
                    @php $isActive = $currentAttention === $value; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['attention' => $value === '' ? null : $value, 'page' => null]) }}"
                       class="op-chip @if($isActive) on @endif">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="op-card"
                 x-data="{
                    selected: [],
                    allIds: @js($orders->pluck('id')->all()),
                    toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : []; },
                    allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                 }">
                {{-- Bulk action bar --}}
                <div x-show="selected.length > 0" x-cloak x-transition.opacity class="op-bulk">
                    <span class="count"><span x-text="selected.length"></span> {{ __('selected') }}</span>
                    <form method="POST" action="{{ route('admin.orders.bulk-status') }}"
                          data-loading-form
                          data-loading-button-text="Processing..."
                          @submit="if (!confirm('{{ __('Apply this status change to the selected orders?') }}')) $event.preventDefault()">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="order_ids[]" :value="id">
                        </template>
                        <div class="op-bulk-field">
                            <label class="op-bulk-label" for="bulk-status-select">{{ __('Bulk status') }}</label>
                            <select id="bulk-status-select" name="status" required>
                                <option value="">{{ __('Choose status') }}</option>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status }}">{{ \App\Models\Order::statusMeta((string) $status)['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="op-btn op-btn-primary op-btn-sm">{{ __('Apply') }}</button>
                        <button type="button" @click="selected = []" class="op-btn op-btn-ghost op-btn-sm">{{ __('Clear') }}</button>
                    </form>
                </div>

                {{-- Filter form --}}
                <form method="GET" action="{{ route('admin.orders.index') }}" class="op-filter-form">
                    @if($currentAttention !== '')
                        <input type="hidden" name="attention" value="{{ $currentAttention }}">
                    @endif
                    <div class="op-filter">
                        <div class="f-search">
                            <label class="f-label" for="filter-search">{{ __('Search') }}</label>
                            <input id="filter-search" class="op-input" type="text" name="search"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('Search order #, city, phone, user...') }}">
                        </div>
                        <div class="f-status">
                            <label class="f-label" for="filter-status">{{ __('Status') }}</label>
                            <select id="filter-status" name="status" class="op-select">
                                <option value="">{{ __('All Statuses') }}</option>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>
                                        {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="f-date">
                            <label class="f-label" for="filter-from">{{ __('From') }}</label>
                            <input id="filter-from" class="op-input" type="date" name="from" value="{{ request('from') }}">
                        </div>
                        <div class="f-date">
                            <label class="f-label" for="filter-to">{{ __('To') }}</label>
                            <input id="filter-to" class="op-input" type="date" name="to" value="{{ request('to') }}">
                        </div>
                        <div class="f-assoc">
                            <label class="f-label" for="filter-assoc">{{ __('User Type') }}</label>
                            <select id="filter-assoc" name="association" class="op-select">
                                <option value="">{{ __('All Users') }}</option>
                                <option value="user" @selected(($association ?? '') === 'user')>{{ __('Retail Users') }}</option>
                                <option value="dealer" @selected(($association ?? '') === 'dealer')>{{ __('Dealers') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="op-actions">
                        @if($hasActiveFilters)
                            <a class="op-btn op-btn-ghost op-btn-sm"
                               href="{{ route('admin.orders.index', $currentAttention !== '' ? ['attention' => $currentAttention] : []) }}">
                                {{ __('Clear') }}
                            </a>
                        @endif
                        <button type="submit" class="op-btn op-btn-primary op-btn-sm">{{ __('Apply Filters') }}</button>
                    </div>
                </form>

                {{-- Table --}}
                <div class="op-table-wrap">
                    <table class="op-tbl">
                        <thead>
                            <tr>
                                <th style="width:32px">
                                    <input type="checkbox"
                                           @change="toggleAll($event)"
                                           :checked="allSelected()"
                                           aria-label="{{ __('Select all') }}">
                                </th>
                                <th>{{ __('Order') }}</th>
                                <th>{{ __('User / Dealer') }}</th>
                                <th class="col-items">{{ __('Items') }}</th>
                                <th class="ralign">{{ __('Total') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="col-date">{{ __('Date') }}</th>
                                <th class="ralign">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $isDealer = $order->user && $order->user->role === \App\Models\User::ROLE_DEALER;
                                    $statusMeta = \App\Models\Order::statusMeta((string) $order->status);
                                    $paymentMeta = \App\Models\Order::paymentStatusMeta((string) $order->payment_status);
                                    $allowedTransitions = $transitionOptions[$order->id] ?? [$order->status];
                                    $canArchive = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
                                @endphp
                                <tr :class="selected.includes({{ $order->id }}) ? 'op-row-selected' : ''">
                                    <td>
                                        <input type="checkbox"
                                               value="{{ $order->id }}"
                                               x-model.number="selected"
                                               aria-label="{{ __('Select order #:order', ['order' => $order->order_number]) }}">
                                    </td>
                                    <td>
                                        <span class="num">{{ $order->order_number }}</span>
                                        <span class="id">#{{ $order->id }}</span>
                                        @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELLED)
                                            <div class="op-alert"><span aria-hidden="true">!</span>{{ __('Cancellation Requested') }}</div>
                                        @endif
                                        @if(($order->open_returns_count ?? 0) > 0)
                                            <div class="op-alert warn"><span aria-hidden="true">R</span>{{ __('Return requests') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="who">{{ $order->user?->name ?? __('Guest customer') }}</div>
                                        <div class="em">{{ $order->user?->email ?? '-' }}</div>
                                        @if($order->user)
                                            <span class="op-tag {{ $isDealer ? 'dealer' : 'user' }}">{{ $isDealer ? __('Dealer') : __('User') }}</span>
                                        @endif
                                    </td>
                                    <td class="col-items items-cnt">{{ $order->items_count }}</td>
                                    <td class="ttl ralign">{{ number_format((float) $order->total_amount, $currencyDecimals) }}<span class="cy">{{ $currencyLabel }}</span></td>
                                    <td>
                                        <span class="op-pill {{ $order->payment_status }}">{{ $paymentMeta['label'] }}</span>
                                        @if($order->payment_method)
                                            <div class="meth">{{ $order->payment_method }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="op-pill {{ $order->status }}">{{ $statusMeta['label'] }}</span>
                                    </td>
                                    <td class="col-date">
                                        <div class="date-d">{{ $order->created_at?->format('M d, Y') }}</div>
                                        <div class="date-t">{{ $order->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="ralign">
                                        <div class="op-acts">
                                            <a class="op-icon" href="{{ route('admin.orders.show', $order) }}" title="{{ __('View') }}" aria-label="{{ __('View order') }}">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            <div x-data="{
                                                    open: false,
                                                    x: 0, y: 0,
                                                    toggle(ev) {
                                                        if (this.open) { this.open = false; return; }
                                                        const r = ev.currentTarget.getBoundingClientRect();
                                                        const menuW = 218;
                                                        const menuH = 174;
                                                        let left = r.right - menuW;
                                                        if (document.documentElement.dir === 'rtl') { left = r.left; }
                                                        if (left < 8) left = 8;
                                                        if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
                                                        let top = r.bottom + 6;
                                                        if (top + menuH > window.innerHeight - 8) top = r.top - menuH - 6;
                                                        this.x = left; this.y = top;
                                                        this.open = true;
                                                    }
                                                 }"
                                                 @keydown.escape.window="open = false"
                                                 @scroll.window="open = false"
                                                 @resize.window="open = false">
                                                <button class="op-icon" @click.stop="toggle($event)" type="button" title="{{ __('Invoice') }}" aria-label="{{ __('Choose invoice language') }}">
                                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </button>
                                                <template x-teleport="body">
                                                    <div class="op-menu op-invoice-menu"
                                                         x-show="open"
                                                         x-cloak
                                                         x-transition.opacity.duration.120ms
                                                         :style="`top:${y}px; left:${x}px;`"
                                                         @click.outside="open = false">
                                                        <div class="head">{{ __('Invoice language') }}</div>
                                                        <a class="invoice-lang" href="{{ route('admin.orders.invoice', ['order' => $order, 'lang' => 'en']) }}">
                                                            <span>{{ __('English') }}</span>
                                                            <span class="invoice-code">EN</span>
                                                        </a>
                                                        <a class="invoice-lang" href="{{ route('admin.orders.invoice', ['order' => $order, 'lang' => 'ar']) }}">
                                                            <span>{{ __('Arabic') }}</span>
                                                            <span class="invoice-code">AR</span>
                                                        </a>
                                                        <a class="invoice-lang" href="{{ route('admin.orders.invoice', ['order' => $order, 'lang' => 'ku']) }}">
                                                            <span>{{ __('Kurdish') }}</span>
                                                            <span class="invoice-code">KU</span>
                                                        </a>
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-data="{
                                                    open: false,
                                                    x: 0, y: 0,
                                                    toggle(ev) {
                                                        if (this.open) { this.open = false; return; }
                                                        const r = ev.currentTarget.getBoundingClientRect();
                                                        const menuW = 270;
                                                        const menuH = {{ $canArchive ? 220 : 160 }};
                                                        let left = r.right - menuW;
                                                        if (document.documentElement.dir === 'rtl') { left = r.left; }
                                                        if (left < 8) left = 8;
                                                        if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
                                                        let top = r.bottom + 6;
                                                        if (top + menuH > window.innerHeight - 8) top = r.top - menuH - 6;
                                                        this.x = left; this.y = top;
                                                        this.open = true;
                                                    }
                                                 }"
                                                 @keydown.escape.window="open = false"
                                                 @scroll.window="open = false"
                                                 @resize.window="open = false">
                                                <button class="op-icon" @click.stop="toggle($event)" type="button" title="{{ __('More') }}" aria-label="{{ __('More actions') }}">
                                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/></svg>
                                                </button>
                                                <template x-teleport="body">
                                                    <div class="op-menu"
                                                         x-show="open"
                                                         x-cloak
                                                         x-transition.opacity.duration.120ms
                                                         :style="`top:${y}px; left:${x}px;`"
                                                         @click.outside="open = false">
                                                        <div class="head">{{ __('Update Status') }}</div>
                                                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" data-loading-form data-loading-button-text="Saving...">
                                                            @csrf
                                                            @method('PATCH')
                                                            <select name="status">
                                                                @foreach($statusOptions as $status)
                                                                    <option value="{{ $status }}"
                                                                            @selected($order->status === $status)
                                                                            @disabled(!in_array($status, $allowedTransitions, true))>
                                                                        {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit">{{ __('Save') }}</button>
                                                        </form>
                                                        @if($canArchive)
                                                            <hr>
                                                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}"
                                                                  data-danger-confirm
                                                                  data-danger-title="{{ __('Archive Order') }}"
                                                                  data-danger-description="{{ __('The order will be hidden from the active order list but kept for financial history and audit review.') }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="danger">{{ __('Archive Order') }}</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="op-empty">
                                            <div class="ico">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2.293 5.16a1 1 0 01-.914.59H7.207a1 1 0 01-.914-.59L4 13m16 0h-5.114a1 1 0 00-.894.553l-.829 1.658a1 1 0 01-.894.553h-2.538a1 1 0 01-.894-.553l-.829-1.658A1 1 0 008.114 13H4"/></svg>
                                            </div>
                                            <div class="title">{{ __('No orders found') }}</div>
                                            <div class="help">{{ __('Try adjusting your filters or clearing them to see all orders.') }}</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="op-pag">
                        <span>{{ __('Showing :from-:to of :total orders', [
                            'from' => $orders->firstItem() ?? 0,
                            'to' => $orders->lastItem() ?? 0,
                            'total' => $orders->total(),
                        ]) }}</span>
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
