from __future__ import annotations

from pathlib import Path

import fitz


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "storage" / "app" / "YallaSpare_NICE_2026_Internet_Figures.pdf"
PREVIEW = ROOT / "storage" / "app" / "YallaSpare_NICE_2026_Internet_Figures_Preview.png"
ASSETS = ROOT / "storage" / "app" / "poster-assets"

W, H = 792, 1583.04
NAVY = (7 / 255, 7 / 255, 64 / 255)
INK = (15 / 255, 23 / 255, 42 / 255)
MUTED = (71 / 255, 85 / 255, 105 / 255)
LINE = (203 / 255, 213 / 255, 225 / 255)
BG = (248 / 255, 250 / 255, 252 / 255)
WHITE = (1, 1, 1)
RED = (220 / 255, 38 / 255, 38 / 255)
BLUE = (37 / 255, 99 / 255, 235 / 255)
GREEN = (16 / 255, 185 / 255, 129 / 255)
AMBER = (245 / 255, 158 / 255, 11 / 255)
TEAL = (20 / 255, 184 / 255, 166 / 255)
SLATE = (71 / 255, 85 / 255, 105 / 255)


def r(x: float, y: float, w: float, h: float) -> fitz.Rect:
    return fitz.Rect(x, y, x + w, y + h)


def txt(page: fitz.Page, box: fitz.Rect, text: str, size: float, color=INK, align=fitz.TEXT_ALIGN_LEFT) -> None:
    page.insert_textbox(box, text, fontsize=size, fontname="helv", color=color, align=align, lineheight=1.06)


def card(page: fitz.Page, box: fitz.Rect, fill=WHITE) -> None:
    page.draw_rect(box, color=LINE, fill=fill, width=0.8)


def title(page: fitz.Page, no: str, heading: str, x: float, y: float, w: float) -> None:
    page.draw_circle((x + 17, y + 17), 17, color=RED, fill=RED)
    txt(page, r(x + 7, y + 5, 20, 22), no, 12.5, WHITE, fitz.TEXT_ALIGN_CENTER)
    txt(page, r(x + 42, y + 3, w - 42, 24), heading, 17, NAVY)
    page.draw_line((x, y + 40), (x + w, y + 40), color=LINE, width=0.8)


def bullet(page: fitz.Page, items: list[str], x: float, y: float, w: float, gap=27, size=9.3) -> None:
    for i, item in enumerate(items):
        yy = y + i * gap
        page.draw_circle((x + 5, yy + 6), 3, color=RED, fill=RED)
        txt(page, r(x + 15, yy, w - 15, gap - 2), item, size, INK)


def image_fit(page: fitz.Page, box: fitz.Rect, path: Path) -> None:
    page.insert_image(box, filename=str(path), keep_proportion=True)


def step(page: fitz.Page, x: float, y: float, w: float, name: str, body: str, fill) -> None:
    page.draw_rect(r(x, y, w, 67), color=fill, fill=fill)
    txt(page, r(x + 8, y + 8, w - 16, 14), name, 9.5, WHITE, fitz.TEXT_ALIGN_CENTER)
    txt(page, r(x + 8, y + 29, w - 16, 28), body, 7.5, WHITE, fitz.TEXT_ALIGN_CENTER)


def build() -> None:
    doc = fitz.open()
    page = doc.new_page(width=W, height=H)
    page.draw_rect(r(0, 0, W, H), color=BG, fill=BG)

    # Header with real warehouse figure.
    page.draw_rect(r(0, 0, W, 250), color=NAVY, fill=NAVY)
    page.draw_rect(r(0, 0, W, 12), color=AMBER, fill=AMBER)
    page.draw_rect(r(0, 224, W, 26), color=(2 / 255, 43 / 255, 58 / 255), fill=(2 / 255, 43 / 255, 58 / 255))
    hero_img = ASSETS / "warehouse_pexels_tiger_lily.jpg"
    image_fit(page, r(468, 34, 138, 112), hero_img)
    page.draw_rect(r(468, 34, 138, 112), color=WHITE, width=1.2)
    page.draw_rect(r(0, 238, W, 12), color=AMBER, fill=AMBER)

    txt(page, r(42, 32, 430, 18), "Tishk International University | NICE 2026", 11.5, (203 / 255, 213 / 255, 225 / 255))
    page.insert_text((42, 86), "YALLASPARE", fontsize=31, fontname="helv", color=WHITE)
    txt(page, r(42, 101, 405, 24), "FIND IT. FIX IT. YALLA!", 14, TEAL)
    txt(page, r(42, 131, 405, 42), "A Laravel-based marketplace and management platform for spare parts, inventory, orders, dealers, and customer shopping.", 11.2, (226 / 255, 232 / 255, 240 / 255))
    txt(page, r(42, 180, 440, 40), "Prepared by: Ali Dilshad Rostam, Rawan Bestoon Kareem, Shanaz Khalil Karim\nClass Code: CM.IT-43", 9.3, WHITE)
    txt(page, r(468, 151, 138, 18), "Warehouse workflow", 7.5, WHITE, fitz.TEXT_ALIGN_CENTER)

    # QR / project code.
    qr = r(644, 44, 84, 84)
    page.draw_rect(r(636, 34, 100, 126), color=TEAL, fill=WHITE, width=1.1)
    image_fit(page, qr, ASSETS / "class_cm_it_43_qr.png")
    txt(page, r(638, 134, 96, 12), "CM.IT-43", 8.5, NAVY, fitz.TEXT_ALIGN_CENTER)
    txt(page, r(638, 147, 96, 10), "Class Code", 7.2, NAVY, fitz.TEXT_ALIGN_CENTER)

    margin, gap = 34, 16
    col = (W - margin * 2 - gap) / 2
    x1, x2 = margin, margin + col + gap

    y = 274
    card(page, r(x1, y, col, 185))
    title(page, "1", "Project Concept", x1 + 18, y + 15, col - 36)
    txt(page, r(x1 + 22, y + 68, col - 44, 74), "YallaSpare is a modern auto spare parts marketplace and administrative system. It connects customer shopping workflows with back-office controls for products, stock, orders, dealers, finance, and platform settings.", 9.7)
    txt(page, r(x1 + 22, y + 145, col - 44, 26), "Purpose: one reliable platform for spare-part discovery, controlled inventory, secure administration, and faster order handling.", 9.1, NAVY)

    card(page, r(x2, y, col, 185))
    title(page, "2", "Main Features", x2 + 18, y + 15, col - 36)
    bullet(page, [
        "Customer marketplace with categories, search, filters, wishlist, reviews, cart, and checkout.",
        "Admin dashboard for products, orders, users, dealers, coupons, revenue, settings, and logs.",
        "Dealer pricing, approval workflow, and role-based permission controls.",
        "Vehicle fitment rules by brand, model, year, and engine.",
    ], x2 + 22, y + 69, col - 44, gap=25, size=8.8)

    y = 482
    card(page, r(margin, y, W - margin * 2, 146))
    title(page, "3", "System Flow", margin + 18, y + 15, W - margin * 2 - 36)
    sx, sy, sw = margin + 22, y + 71, 128
    flows = [
        ("Product Data", "SKU, OEM, brand,\nstock, image, category", BLUE),
        ("Customer Shop", "Search, filters,\nfitment, cart", RED),
        ("Checkout", "Guest/user orders\nwith totals and status", AMBER),
        ("Admin Control", "Inventory, invoices,\nreturns, approvals", NAVY),
        ("Reports & Logs", "Revenue, low stock,\naudit activity", GREEN),
    ]
    for i, (a, b, c) in enumerate(flows):
        step(page, sx + i * (sw + 11), sy, sw, a, b, c)

    y = 650
    card(page, r(x1, y, col, 313))
    title(page, "4", "Materials and Procedure", x1 + 18, y + 15, col - 36)
    tech = [
        ("PHP", "Laravel\nBackend MVC", RED),
        ("DB", "MySQL\nRelational data", BLUE),
        ("TW", "Tailwind\nResponsive UI", GREEN),
        ("JS", "Vite\nFrontend assets", AMBER),
        ("PDF", "DomPDF\nInvoices", NAVY),
        ("XLS", "Excel\nImport/export", SLATE),
    ]
    for i, (code, label, color) in enumerate(tech):
        bx = x1 + 24 + (i % 3) * 100
        by = y + 72 + (i // 3) * 82
        page.draw_rect(r(bx, by, 88, 66), color=LINE, fill=WHITE, width=0.8)
        page.draw_circle((bx + 21, by + 23), 15, color=color, fill=color)
        txt(page, r(bx + 10, by + 15, 22, 15), code, 7.5, WHITE, fitz.TEXT_ALIGN_CENTER)
        txt(page, r(bx + 43, by + 13, 36, 35), label, 7.7, INK)
    txt(page, r(x1 + 22, y + 236, col - 44, 45), "Procedure: define product/catalog data, validate user input, process cart and checkout flows, update stock movements, generate invoices, and record administrative activity.", 8.8)

    card(page, r(x2, y, col, 313))
    title(page, "5", "Internet Figures", x2 + 18, y + 15, col - 36)
    image_fit(page, r(x2 + 22, y + 70, 145, 96), ASSETS / "warehouse_pixabay_emkanicepic.jpg")
    image_fit(page, r(x2 + 178, y + 70, 145, 96), ASSETS / "warehouse_unsplash_lim_woojung.jpg")
    txt(page, r(x2 + 22, y + 178, col - 44, 50), "The added figures show warehouse shelving, stock locations, and picking lanes. These visuals support the inventory and product-location workflow represented in YallaSpare.", 9.2)
    txt(page, r(x2 + 22, y + 238, col - 44, 40), "Use in presentation: explain how product data, stock level, and storage location connect to admin operations.", 8.5, MUTED)

    y = 988
    card(page, r(x1, y, col, 244))
    title(page, "6", "Key Outputs", x1 + 18, y + 15, col - 36)
    bullet(page, [
        "Structured catalog with categories, product images, pricing, stock, SKU, OEM, and part numbers.",
        "Order lifecycle management with status history, payment status, returns, cancellation requests, and invoices.",
        "Admin notifications for low stock, out-of-stock items, and pending dealer requests.",
        "Excel product import/export and revenue reporting.",
        "User account pages for profile, addresses, settings, activity, orders, and wishlist.",
    ], x1 + 22, y + 70, col - 44, gap=31, size=8.5)

    card(page, r(x2, y, col, 244))
    title(page, "7", "Product Evidence", x2 + 18, y + 15, col - 36)
    products = sorted((ROOT / "public" / "storage" / "products").glob("*"))[:5]
    for i, p in enumerate(products):
        px = x2 + 22 + i * 61
        page.draw_rect(r(px, y + 72, 54, 54), color=LINE, fill=WHITE, width=0.7)
        try:
            image_fit(page, r(px + 4, y + 76, 46, 46), p)
        except Exception:
            pass
    txt(page, r(x2 + 22, y + 145, col - 44, 55), "The public marketplace is supported by stored product images while the admin side controls stock levels, dealer prices, vehicle compatibility, and financial operations.", 9.0)

    y = 1260
    card(page, r(margin, y, W - margin * 2, 180))
    title(page, "8", "Conclusion and Future Work", margin + 18, y + 15, W - margin * 2 - 36)
    bullet(page, [
        "YallaSpare demonstrates a complete marketplace plus operations platform for spare-parts businesses.",
        "The implementation combines customer-facing shopping with admin-grade permissions, finance controls, audit logs, and inventory workflows.",
        "Future development can add supplier integrations, barcode scanning, AI-based part recommendations, mobile apps, and live delivery tracking.",
    ], margin + 24, y + 70, W - margin * 2 - 48, gap=31, size=9.1)

    page.draw_rect(r(0, H - 76, W, 76), color=NAVY, fill=NAVY)
    txt(page, r(36, H - 62, 520, 16), "Sources: Pexels/Tiger Lily warehouse photo; Pixabay/emkanicepic warehouse shelves; Unsplash/lim woojung picking shelves.", 7.6, WHITE)
    txt(page, r(36, H - 42, 520, 16), "YallaSpare Project | Laravel, PHP, MySQL, Tailwind CSS | Auto Parts Marketplace and Management Platform", 7.6, WHITE)
    txt(page, r(585, H - 52, 160, 20), "PDF poster with internet figures", 8, WHITE, fitz.TEXT_ALIGN_RIGHT)

    doc.save(OUT, garbage=4, deflate=True)
    pix = page.get_pixmap(matrix=fitz.Matrix(0.8, 0.8), alpha=False)
    pix.save(PREVIEW)
    doc.close()
    print(OUT)
    print(PREVIEW)


if __name__ == "__main__":
    build()
