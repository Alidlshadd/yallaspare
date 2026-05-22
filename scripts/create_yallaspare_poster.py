from __future__ import annotations

from pathlib import Path
import random

import fitz


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "storage" / "app"
OUT_PATH = OUT_DIR / "YallaSpare_NICE_2026_Poster_EDITABLE.pdf"

PAGE_W = 792
PAGE_H = 1583.04

NAVY = (7 / 255, 7 / 255, 64 / 255)
NAVY_2 = (18 / 255, 24 / 255, 52 / 255)
BLUE = (37 / 255, 99 / 255, 235 / 255)
RED = (220 / 255, 38 / 255, 38 / 255)
SLATE = (71 / 255, 85 / 255, 105 / 255)
LIGHT = (248 / 255, 250 / 255, 252 / 255)
BORDER = (203 / 255, 213 / 255, 225 / 255)
WHITE = (1, 1, 1)
INK = (15 / 255, 23 / 255, 42 / 255)
MUTED = (82 / 255, 94 / 255, 115 / 255)
GREEN = (16 / 255, 185 / 255, 129 / 255)
AMBER = (245 / 255, 158 / 255, 11 / 255)


def rect(x: float, y: float, w: float, h: float) -> fitz.Rect:
    return fitz.Rect(x, y, x + w, y + h)


def draw_text(
    page: fitz.Page,
    box: fitz.Rect,
    text: str,
    size: float = 12,
    color=INK,
    font: str = "helv",
    align: int = fitz.TEXT_ALIGN_LEFT,
    lineheight: float | None = None,
) -> None:
    page.insert_textbox(
        box,
        text,
        fontsize=size,
        fontname=font,
        color=color,
        align=align,
        lineheight=lineheight,
    )


def draw_card(page: fitz.Page, box: fitz.Rect, fill=WHITE, stroke=BORDER, radius: float = 8) -> None:
    page.draw_rect(box, color=stroke, fill=fill, width=0.8)


def draw_section_title(page: fitz.Page, number: str, title: str, x: float, y: float, w: float) -> None:
    page.draw_circle((x + 18, y + 16), 17, color=RED, fill=RED)
    draw_text(page, rect(x + 8, y + 4, 20, 24), number, 13, WHITE, font="helv", align=fitz.TEXT_ALIGN_CENTER)
    draw_text(page, rect(x + 42, y + 1, w - 42, 30), title, 18, NAVY, font="helv")
    page.draw_line((x, y + 39), (x + w, y + 39), color=BORDER, width=0.8)


def bullet_list(page: fitz.Page, items: list[str], x: float, y: float, w: float, size: float = 10.5, gap: float = 31) -> None:
    for i, item in enumerate(items):
        yy = y + i * gap
        page.draw_circle((x + 5, yy + 6), 3.2, color=RED, fill=RED)
        draw_text(page, rect(x + 15, yy, w - 15, gap - 2), item, size, INK, lineheight=1.08)


def stat_box(page: fitz.Page, x: float, y: float, w: float, h: float, value: str, label: str, fill) -> None:
    page.draw_rect(rect(x, y, w, h), color=fill, fill=fill)
    draw_text(page, rect(x + 8, y + 10, w - 16, 24), value, 17, WHITE, font="helv", align=fitz.TEXT_ALIGN_CENTER)
    draw_text(page, rect(x + 8, y + 35, w - 16, h - 40), label, 8.7, WHITE, align=fitz.TEXT_ALIGN_CENTER)


def flow_step(page: fitz.Page, x: float, y: float, w: float, title: str, body: str, fill) -> None:
    page.draw_rect(rect(x, y, w, 77), color=fill, fill=fill)
    draw_text(page, rect(x + 10, y + 9, w - 20, 18), title, 11, WHITE, font="helv", align=fitz.TEXT_ALIGN_CENTER)
    draw_text(page, rect(x + 10, y + 31, w - 20, 40), body, 8.8, WHITE, align=fitz.TEXT_ALIGN_CENTER, lineheight=1.04)


def tech_badge(page: fitz.Page, x: float, y: float, code: str, name: str, desc: str, fill) -> None:
    page.draw_rect(rect(x, y, 82, 78), color=BORDER, fill=WHITE)
    page.draw_circle((x + 22, y + 25), 16, color=fill, fill=fill)
    draw_text(page, rect(x + 8, y + 15, 28, 20), code, 9, WHITE, font="helv", align=fitz.TEXT_ALIGN_CENTER)
    draw_text(page, rect(x + 43, y + 13, 34, 16), name, 8.5, INK, font="helv")
    draw_text(page, rect(x + 8, y + 47, 66, 22), desc, 7.5, MUTED, align=fitz.TEXT_ALIGN_CENTER, lineheight=1.0)


def draw_mock_dashboard(page: fitz.Page, box: fitz.Rect) -> None:
    page.draw_rect(box, color=NAVY_2, fill=NAVY_2)
    page.draw_rect(rect(box.x0, box.y0, box.width, 30), color=NAVY, fill=NAVY)
    for i, c in enumerate([RED, AMBER, GREEN]):
        page.draw_circle((box.x0 + 18 + i * 18, box.y0 + 15), 5.5, color=c, fill=c)
    draw_text(page, rect(box.x0 + 325, box.y0 + 8, 80, 15), "Admin View", 8, WHITE, align=fitz.TEXT_ALIGN_RIGHT)

    left = box.x0 + 18
    top = box.y0 + 48
    page.draw_rect(rect(left, top, 82, 164), color=(30 / 255, 41 / 255, 59 / 255), fill=(30 / 255, 41 / 255, 59 / 255))
    for i, label in enumerate(["Dashboard", "Products", "Orders", "Dealers", "Reports"]):
        yy = top + 16 + i * 27
        color = RED if i == 0 else (148 / 255, 163 / 255, 184 / 255)
        page.draw_rect(rect(left + 10, yy, 8, 8), color=color, fill=color)
        draw_text(page, rect(left + 24, yy - 3, 48, 12), label, 6.8, WHITE if i == 0 else (203 / 255, 213 / 255, 225 / 255))

    stat_box(page, box.x0 + 118, top, 82, 58, "248", "Products", BLUE)
    stat_box(page, box.x0 + 212, top, 82, 58, "37", "Orders", RED)
    stat_box(page, box.x0 + 306, top, 82, 58, "12", "Dealers", GREEN)

    chart = rect(box.x0 + 118, top + 78, 270, 86)
    page.draw_rect(chart, color=(51 / 255, 65 / 255, 85 / 255), fill=(51 / 255, 65 / 255, 85 / 255))
    random.seed(4)
    points = []
    for i in range(9):
        px = chart.x0 + 18 + i * 29
        py = chart.y1 - 18 - random.randint(5, 45)
        points.append((px, py))
    for i in range(len(points) - 1):
        page.draw_line(points[i], points[i + 1], color=RED, width=2.2)
    for p in points:
        page.draw_circle(p, 3.5, color=WHITE, fill=WHITE)


def add_product_strip(page: fitz.Page, x: float, y: float, w: float) -> None:
    images = sorted((ROOT / "public" / "storage" / "products").glob("*"))[:6]
    cell_w = (w - 25) / 6
    for idx, image in enumerate(images):
        bx = x + idx * (cell_w + 5)
        card = rect(bx, y, cell_w, 56)
        page.draw_rect(card, color=BORDER, fill=WHITE)
        try:
            page.insert_image(rect(bx + 5, y + 5, cell_w - 10, 46), filename=str(image), keep_proportion=True)
        except Exception:
            page.draw_rect(rect(bx + 12, y + 14, cell_w - 24, 28), color=BLUE, fill=LIGHT)


def build() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    doc = fitz.open()
    page = doc.new_page(width=PAGE_W, height=PAGE_H)

    page.draw_rect(rect(0, 0, PAGE_W, PAGE_H), color=LIGHT, fill=LIGHT)
    page.draw_rect(rect(0, 0, PAGE_W, 224), color=NAVY, fill=NAVY)
    page.draw_rect(rect(0, 212, PAGE_W, 12), color=RED, fill=RED)
    page.draw_circle((690, 64), 90, color=(20 / 255, 31 / 255, 72 / 255), fill=(20 / 255, 31 / 255, 72 / 255))
    page.draw_circle((748, 150), 92, color=(120 / 255, 24 / 255, 40 / 255), fill=(120 / 255, 24 / 255, 40 / 255))

    draw_text(page, rect(42, 34, 330, 22), "YallaSpare Project Poster", 13, (203 / 255, 213 / 255, 225 / 255), font="helv")
    draw_text(page, rect(42, 62, 500, 66), "YallaSpare Auto Parts System", 31, WHITE, font="helv", lineheight=0.95)
    draw_text(
        page,
        rect(42, 126, 475, 43),
        "A Laravel-based marketplace and management platform for spare parts, inventory, orders, dealers, and customer shopping.",
        12.5,
        (226 / 255, 232 / 255, 240 / 255),
        lineheight=1.08,
    )
    draw_text(page, rect(42, 176, 440, 20), "NICE 2026 | National Innovation Competition Exhibition", 12, WHITE, font="helv")

    qr = rect(610, 38, 132, 132)
    page.draw_rect(qr, color=WHITE, fill=WHITE)
    for r in range(9):
        for c in range(9):
            if (r * 3 + c * 5 + r * c) % 4 in (0, 1):
                page.draw_rect(rect(qr.x0 + 12 + c * 12, qr.y0 + 12 + r * 12, 8, 8), color=NAVY, fill=NAVY)
    draw_text(page, rect(610, 176, 132, 14), "QR Code & Project Code", 8.5, WHITE, font="helv", align=fitz.TEXT_ALIGN_CENTER)
    draw_text(page, rect(610, 193, 132, 18), "YS-NICE-2026", 10.5, WHITE, font="helv", align=fitz.TEXT_ALIGN_CENTER)

    margin = 36
    gap = 18
    col_w = (PAGE_W - margin * 2 - gap) / 2
    left_x = margin
    right_x = margin + col_w + gap

    y = 250
    draw_card(page, rect(left_x, y, col_w, 205))
    draw_section_title(page, "1", "Project Concept", left_x + 18, y + 16, col_w - 36)
    draw_text(
        page,
        rect(left_x + 22, y + 68, col_w - 44, 86),
        "YallaSpare is a modern auto spare parts marketplace and administrative system. It connects customer shopping workflows with back-office controls for stock, orders, dealers, finance, and platform settings.",
        10.8,
        INK,
        lineheight=1.13,
    )
    draw_text(
        page,
        rect(left_x + 22, y + 154, col_w - 44, 34),
        "Purpose: provide a single reliable platform for spare-part discovery, controlled inventory, secure administration, and faster order handling.",
        10.3,
        NAVY,
        font="helv",
        lineheight=1.08,
    )

    draw_card(page, rect(right_x, y, col_w, 205))
    draw_section_title(page, "2", "Core Modules", right_x + 18, y + 16, col_w - 36)
    bullet_list(
        page,
        [
            "Customer marketplace with categories, search, filters, wishlist, reviews, cart, and checkout.",
            "Admin dashboard for products, orders, users, dealers, revenue, coupons, settings, and logs.",
            "Dealer pricing, dealer approval workflow, role-based permissions, and account controls.",
            "Vehicle fitment rules to match products with brand, model, year, and engine data.",
        ],
        right_x + 22,
        y + 68,
        col_w - 44,
        size=9.8,
        gap=30,
    )

    y = 482
    draw_card(page, rect(margin, y, PAGE_W - margin * 2, 174))
    draw_section_title(page, "3", "System Flow", margin + 18, y + 16, PAGE_W - margin * 2 - 36)
    sx = margin + 26
    sy = y + 75
    step_w = 126
    steps = [
        ("Product Data", "SKU, OEM number, brand, stock, image, category", BLUE),
        ("Customer Shop", "Search, filter, vehicle matching, wishlist, cart", RED),
        ("Checkout", "Guest or user orders with totals and status", AMBER),
        ("Admin Control", "Inventory, invoices, returns, approvals, notes", NAVY),
        ("Audit & Reports", "Activity logs, revenue reports, low-stock alerts", GREEN),
    ]
    for i, (title, body, fill) in enumerate(steps):
        x = sx + i * (step_w + 15)
        flow_step(page, x, sy, step_w, title, body, fill)
        if i < len(steps) - 1:
            page.draw_line((x + step_w + 3, sy + 38), (x + step_w + 12, sy + 38), color=SLATE, width=1.2)
            page.draw_line((x + step_w + 12, sy + 38), (x + step_w + 7, sy + 34), color=SLATE, width=1.2)
            page.draw_line((x + step_w + 12, sy + 38), (x + step_w + 7, sy + 42), color=SLATE, width=1.2)

    y = 680
    draw_card(page, rect(left_x, y, col_w, 318))
    draw_section_title(page, "4", "Materials and Procedure", left_x + 18, y + 16, col_w - 36)
    tx = left_x + 23
    ty = y + 68
    techs = [
        ("PHP", "Laravel", "Backend MVC", RED),
        ("DB", "MySQL", "Relational data", BLUE),
        ("TW", "Tailwind", "Responsive UI", GREEN),
        ("JS", "Alpine/Vite", "Frontend assets", AMBER),
        ("PDF", "DomPDF", "Invoices", NAVY),
        ("XLS", "Excel", "Import/export", SLATE),
    ]
    for i, tech in enumerate(techs):
        tech_badge(page, tx + (i % 3) * 103, ty + (i // 3) * 91, *tech)
    draw_text(page, rect(left_x + 22, y + 250, col_w - 44, 48), "Procedure: define product/catalog data, validate user input, process cart and checkout flows, update stock movements, generate invoices, and record administrative activity for accountability.", 9.5, INK, lineheight=1.1)

    draw_card(page, rect(right_x, y, col_w, 318))
    draw_section_title(page, "5", "Interface Preview", right_x + 18, y + 16, col_w - 36)
    draw_mock_dashboard(page, rect(right_x + 22, y + 66, col_w - 44, 225))
    draw_text(page, rect(right_x + 24, y + 296, col_w - 48, 15), "Representative admin dashboard concept based on implemented modules.", 7.8, MUTED, align=fitz.TEXT_ALIGN_CENTER)

    y = 1024
    draw_card(page, rect(left_x, y, col_w, 250))
    draw_section_title(page, "6", "Key Outputs", left_x + 18, y + 16, col_w - 36)
    bullet_list(
        page,
        [
            "Structured product catalog with categories, images, pricing, stock quantity, SKU, OEM, and part number fields.",
            "Order lifecycle management with status history, payment status, return/cancellation requests, and invoice PDFs.",
            "Admin notifications for low stock, out-of-stock items, and pending dealer requests.",
            "Excel product import/export for faster bulk data management.",
            "User account pages for profile, addresses, settings, activity, orders, and wishlist.",
        ],
        left_x + 22,
        y + 68,
        col_w - 44,
        size=9.55,
        gap=33,
    )

    draw_card(page, rect(right_x, y, col_w, 250))
    draw_section_title(page, "7", "Product Evidence", right_x + 18, y + 16, col_w - 36)
    add_product_strip(page, right_x + 22, y + 72, col_w - 44)
    draw_text(
        page,
        rect(right_x + 22, y + 148, col_w - 44, 78),
        "The public marketplace is supported by stored product and category images, while the admin side controls stock levels, dealer-specific prices, vehicle compatibility, and financial operations.",
        10.2,
        INK,
        lineheight=1.12,
    )

    y = 1300
    draw_card(page, rect(margin, y, PAGE_W - margin * 2, 184), fill=WHITE)
    draw_section_title(page, "8", "Conclusion and Future Work", margin + 18, y + 16, PAGE_W - margin * 2 - 36)
    bullet_list(
        page,
        [
            "YallaSpare demonstrates a complete marketplace plus operations platform for spare-parts businesses.",
            "The implementation combines customer-facing shopping with admin-grade permissions, finance controls, audit logs, and inventory workflows.",
            "Future development can add supplier integrations, barcode scanning, AI-based part recommendations, mobile apps, and live delivery tracking.",
        ],
        margin + 26,
        y + 70,
        PAGE_W - margin * 2 - 52,
        size=10.2,
        gap=32,
    )

    page.draw_rect(rect(0, PAGE_H - 58, PAGE_W, 58), color=NAVY, fill=NAVY)
    draw_text(page, rect(42, PAGE_H - 42, 360, 22), "Prepared for: YallaSpare Project | Built with Laravel, PHP, MySQL, Tailwind CSS", 9.5, WHITE)
    draw_text(page, rect(563, PAGE_H - 42, 188, 22), "Editable text-based PDF", 9.5, WHITE, font="helv", align=fitz.TEXT_ALIGN_RIGHT)

    doc.save(OUT_PATH, garbage=4, deflate=True)
    doc.close()
    print(OUT_PATH)


if __name__ == "__main__":
    build()
