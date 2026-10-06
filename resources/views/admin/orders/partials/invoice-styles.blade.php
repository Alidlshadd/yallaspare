{{-- The look of every invoice the shop issues. Shared by the order invoice and
     the manual one so the two cannot drift into different documents. --}}
<style>
    @page {
        margin: 26px 30px 58px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: #ffffff;
        color: #111827;
        {{-- Named here as well as in the engine because a body rule wins
             over mPDF's default_font. Plex joins ڕ and ڵ, which DejaVu
             draws detached, and is the face the storefront uses; Latin
             runs it does not cover fall through to DejaVu. --}}
        font-family: {{ $isRtl ? 'ibmplexsansarabic, DejaVu Sans' : 'DejaVu Sans' }}, sans-serif;
        font-size: 12px;
        line-height: 1.45;
        margin: 0;
    }

    body.ltr {
        direction: ltr;
        text-align: left;
    }

    body.rtl {
        direction: rtl;
        text-align: right;
        font-size: 13.5px;
        line-height: 1.65;
    }

    body.rtl .invoice-title {
        font-size: 28px;
    }

    body.rtl .items-table td,
    body.rtl .items-table th {
        padding: 11px 10px;
    }

    body.rtl .summary-table {
        width: 340px;
    }

    body.rtl .summary-table td {
        padding: 11px 13px;
    }

    body.rtl .print-note,
    body.rtl .invoice-policies {
        padding: 12px 14px;
    }

    body.rtl .info-card-body {
        padding: 14px 16px;
    }

    body.rtl table,
    body.rtl tr,
    body.rtl td,
    body.rtl th,
    body.rtl p,
    body.rtl div,
    body.rtl span {
        direction: rtl !important;
        text-align: right !important;
        unicode-bidi: embed;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    body.rtl .text-center {
        text-align: center !important;
    }

    body.rtl .logo-box {
        text-align: center !important;
    }

    .muted {
        color: #6b7280;
    }

    .navy {
        color: #070740;
    }

    .header-table {
        border-bottom: 4px solid #070740;
        margin-bottom: 22px;
        padding-bottom: 14px;
    }

    .header-table td {
        vertical-align: top;
    }

    .logo-box {
        background: #070740;
        border-radius: 8px;
        color: #ffffff;
        display: inline-block;
        font-size: 16px;
        font-weight: 700;
        height: 56px;
        letter-spacing: 1px;
        line-height: 56px;
        text-align: center;
        width: 56px;
    }

    .logo-img {
        display: block;
        max-height: 58px;
        max-width: 150px;
    }

    .company-name {
        color: #070740;
        font-size: 18px;
        font-weight: 700;
        margin: 8px 0 2px;
    }

    .company-address {
        color: #6b7280;
        font-size: 11px;
        margin: 0;
    }

    .invoice-title {
        color: #070740;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0 0 8px;
    }

    .invoice-meta {
        color: #374151;
        font-size: 11px;
        margin: 0;
    }

    .label {
        color: #6b7280;
        display: block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .5px;
        margin-bottom: 3px;
        text-transform: uppercase;
    }

    .value {
        color: #111827;
        font-weight: 700;
    }

    .cards-table {
        margin-bottom: 20px;
    }

    .cards-table td {
        vertical-align: top;
        width: 49%;
    }

    .card-spacer {
        width: 2% !important;
    }

    .info-card {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        border-collapse: separate;
        width: 100%;
    }

    .info-card-body {
        padding: 12px 14px;
    }

    .meta-label {
        color: #6b7280;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .card-title {
        background: #f3f4f6;
        border-bottom: 1px solid #d1d5db;
        color: #070740;
        font-size: 11px;
        font-weight: 700;
        padding: 8px 14px;
        text-transform: uppercase;
    }

    .status-badge {
        background: #070740;
        border-radius: 12px;
        color: #ffffff;
        display: inline-block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 4px 10px;
        text-transform: uppercase;
    }

    .items-table {
        margin-top: 6px;
    }

    .items-table th {
        background: #070740;
        border: 1px solid #070740;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        padding: 9px 8px;
        text-align: left;
        text-transform: uppercase;
    }

    body.rtl .items-table th {
        text-align: right !important;
    }

    body.rtl .text-right {
        text-align: right !important;
    }

    body.rtl .summary-table {
        margin-left: 0;
        margin-right: auto;
    }

    body.rtl .print-note,
    body.rtl .invoice-policies {
        text-align: right !important;
    }

    .items-table td {
        border: 1px solid #d1d5db;
        padding: 9px 8px;
        vertical-align: top;
    }

    .items-table tbody tr:nth-child(even) td {
        background: #f9fafb;
    }

    .product-name {
        color: #111827;
        font-weight: 700;
    }

    .sku {
        color: #6b7280;
        font-size: 10px;
    }

    .summary-table {
        margin-left: auto;
        margin-top: 18px;
        width: 310px;
    }

    .summary-table td {
        border: 1px solid #d1d5db;
        padding: 9px 11px;
    }

    .summary-table .summary-label {
        background: #f3f4f6;
        color: #374151;
        font-weight: 700;
    }

    .summary-table .grand td {
        background: #070740;
        border-color: #070740;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
    }

    .print-note {
        background: #f9fafb;
        border: 1px solid #d1d5db;
        color: #374151;
        font-size: 11px;
        margin-top: 18px;
        padding: 10px 12px;
    }

    .invoice-policies {
        background: #ffffff;
        border: 1px solid #d1d5db;
        color: #374151;
        font-size: 10.8px;
        margin-top: 12px;
        padding: 10px 12px;
    }

    .invoice-policies-title {
        color: #070740;
        font-weight: 700;
    }

    .invoice-policies p,
    .print-note p {
        margin: 0;
    }

    .invoice-policies p + p,
    .print-note p + p {
        margin-top: 2px;
    }

    /* Vertical breath between different policy blocks
       (return → warranty): each policy has 2 paragraphs,
       so the 3rd paragraph starts a new policy block. */
    .invoice-policies p:nth-of-type(3) {
        margin-top: 8px;
    }

    .footer {
        border-top: 1px solid #d1d5db;
        bottom: -34px;
        color: #6b7280;
        font-size: 10.5px;
        left: 0;
        line-height: 1.5;
        padding-top: 9px;
        position: fixed;
        right: 0;
        text-align: center;
    }
</style>
