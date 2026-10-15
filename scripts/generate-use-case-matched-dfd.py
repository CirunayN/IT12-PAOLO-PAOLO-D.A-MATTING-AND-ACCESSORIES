"""Align the retained business Level 0 DFD with the supplied system use case.

Only documentation artifacts are generated. Original diagrams are preserved.
Customer and Labor, and the previous D3 service records, remain as requested.
Those service flows are conceptual business flows, not new application features.
"""
from __future__ import annotations

import csv
import importlib.util
import json
from pathlib import Path
import sys
import xml.etree.ElementTree as ET

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "dfd"
STEM = "Level-0-DFD-Use-Case-Matched"
spec = importlib.util.spec_from_file_location("classic_dfd", Path(__file__).with_name("generate-classic-dfd.py"))
classic = importlib.util.module_from_spec(spec)
spec.loader.exec_module(classic)

USE_CASES = [
    ("Log In", "4.0", "Admin/Owner; Cashier"),
    ("Create User Account", "4.0", "Admin/Owner"),
    ("Enable or Disable User Account", "4.0", "Admin/Owner"),
    ("Add Product", "1.0", "Admin/Owner"),
    ("Update Product", "1.0", "Admin/Owner"),
    ("View Inventory", "1.0", "Admin/Owner"),
    ("Record Incoming Stock", "1.0", "Admin/Owner"),
    ("Process Sales Transaction", "2.0", "Admin/Owner; Cashier"),
    ("View Sales History", "2.0", "Admin/Owner; Cashier (own transactions only)"),
    ("Print Sales Receipt", "2.0", "Admin/Owner; Cashier"),
    ("Generate Sales Report", "3.0", "Admin/Owner"),
    ("Generate Inventory Report", "3.0", "Admin/Owner"),
    ("Print or Download Reports", "3.0", "Admin/Owner"),
    ("Create Database Backup", "5.0", "Admin/Owner"),
    ("Restore Database Backup", "5.0", "Admin/Owner"),
]


def textparts(n):
    w, h = n["w"], n["h"]
    if n["kind"] == "process":
        return [(n["ref"], 8, 1, w-16, 27, 20, True),
                (n["title"], 10, 36, w-20, 66, 25, True),
                (n["body"], 12, 111, w-24, h-119, 19, False)]
    if n["kind"] == "external":
        return [(n["title"], 10, 0, w-20, h, 24, True)]
    return [(n["ref"], 2, 0, 49, h, 22, True),
            (n["title"], 64, 8, w-78, 35, 22, True),
            (n["body"], 64, 47, w-78, h-55, 17, False)]


classic.textparts = textparts


def actor(d, ident, name, y, height=90):
    return classic.external(d, ident, 60, y, name, 280, height)


def process(d, ident, y, title, body, height):
    return classic.process(d, ident, 930, y, title, body, 460, height)


def store(d, ident, ref, y, title, body):
    return classic.store(d, ident, ref, 2070, y, title, body, 470, 130)


def left_flow(d, actor_id, process_id, label, incoming, label_y, process_fraction, lane=0):
    side = "right" if incoming else "left"
    source, target = (actor_id, process_id) if incoming else (process_id, actor_id)
    sf, tf = (.28, process_fraction) if incoming else (process_fraction, .75)
    a = d.point(source, side, sf)
    b = d.point(target, "left" if incoming else "right", tf)
    label_h = len(label.splitlines()) * 23 + 10
    line_y = label_y + label_h / 2
    near_actor = 370 + lane * 10 + (0 if incoming else 5)
    near_process = 880 + lane * 10 + (0 if incoming else 5)
    via = ([(near_actor, a[1]), (near_actor, line_y),
            (near_process, line_y), (near_process, b[1])] if incoming else
           [(near_process, a[1]), (near_process, line_y),
            (near_actor, line_y), (near_actor, b[1])])
    classic.flow(d, source, target, label, side, "left" if incoming else "right",
                 sf, tf, via, (415, label_y), 440, 18)


def right_flow(d, process_id, store_id, label, write, label_y, process_fraction, lane=0):
    source, target = (process_id, store_id) if write else (store_id, process_id)
    side = "right" if write else "left"
    sf, tf = (process_fraction, .28) if write else (.75, process_fraction)
    a, b = d.point(source, side, sf), d.point(target, "left" if write else "right", tf)
    label_h = len(label.splitlines()) * 23 + 10
    line_y = label_y + label_h / 2
    near_process = 1430 + lane * 13 + (0 if write else 6)
    near_store = 2020 - lane * 13 - (0 if write else 6)
    via = ([(near_process, a[1]), (near_process, line_y),
            (near_store, line_y), (near_store, b[1])] if write else
           [(near_store, a[1]), (near_store, line_y),
            (near_process, line_y), (near_process, b[1])])
    classic.flow(d, source, target, label, side, "left" if write else "right",
                 sf, tf, via, (1510, label_y), 490, 18)


def overview():
    d = classic.Diagram("level-0-use-case-matched", "Level 0 - Use case matched",
                        "Figure 3. Level 0 Data Flow Diagram", "", 2600, 3340, [])

    # Repeated external rectangles identify the same actor, not new entities.
    actor(d, "owner-accounts", "Admin/Owner", 190)
    actor(d, "cashier-accounts", "Cashier", 410)
    process(d, "4.0", 205, "Account and Access\nManagement",
            "Log in\nCreate user account\nEnable or disable user account", 255)
    store(d, "accounts", "D4", 280, "User Account Records",
          "Username, password hash, role\nAccess status and sessions")
    left_flow(d, "owner-accounts", "4.0", "Login credentials, new account details\nand enable/disable request", True, 175, .18)
    left_flow(d, "owner-accounts", "4.0", "Login result, created account\nand updated access status", False, 285, .35)
    left_flow(d, "cashier-accounts", "4.0", "Cashier username and password", True, 410, .68, 1)
    left_flow(d, "cashier-accounts", "4.0", "Login result and cashier access", False, 500, .88, 1)
    right_flow(d, "4.0", "accounts", "New account, access status\nand authenticated session", True, 195, .20)
    right_flow(d, "4.0", "accounts", "Stored credentials, role\nand current access status", False, 420, .78)

    actor(d, "owner-inventory", "Admin/Owner", 690)
    process(d, "1.0", 615, "Inventory Management",
            "Add product\nUpdate product\nView inventory\nRecord incoming stock", 255)
    store(d, "products-inventory", "D1", 685, "Product Records",
          "Products, categories and prices\nIncoming stock and stock balances")
    left_flow(d, "owner-inventory", "1.0", "Product details, updated values,\nincoming-stock details and filters", True, 610, .25)
    left_flow(d, "owner-inventory", "1.0", "Saved changes, current inventory\nand incoming-stock confirmation", False, 805, .80)
    right_flow(d, "1.0", "products-inventory", "New or updated product details\nand received-stock records", True, 610, .25)
    right_flow(d, "1.0", "products-inventory", "Existing products, prices\nand available stock quantities", False, 810, .80)

    actor(d, "owner-sales", "Admin/Owner", 1040)
    actor(d, "cashier-sales", "Cashier", 1240)
    actor(d, "customer", "Customer", 1440)
    actor(d, "labor", "Labor", 1640)
    process(d, "2.0", 1240, "Sales Management",
            "Process sales transaction\nView sales history\nPrint sales receipt\nCashier: own sales history only\nRetain service business data*", 345)
    store(d, "products-sales", "D1", 1090, "Product Records",
          "Product availability, selling prices\nand remaining stock quantities")
    store(d, "product-sales", "D2", 1380, "Product Sale Records",
          "Cashier, date, items and quantities\nTotals and Cash/GCash payment")
    store(d, "service-sales", "D3", 1650, "Service Sale Records*",
          "Service description, charge, status\nLinked transaction and completion")
    left_flow(d, "owner-sales", "2.0", "Sales transaction details, history\nselection and receipt request", True, 1010, .10)
    left_flow(d, "owner-sales", "2.0", "Transaction result, sales history\nand printable sales receipt", False, 1120, .18)
    left_flow(d, "cashier-sales", "2.0", "Sales details, own-history selection\nand receipt request", True, 1210, .32, 1)
    left_flow(d, "cashier-sales", "2.0", "Transaction result, own sales history\nand printable sales receipt", False, 1320, .42, 1)
    left_flow(d, "customer", "2.0", "Order and payment details\n(Cash/GCash, amount and reference)", True, 1410, .60, 2)
    left_flow(d, "customer", "2.0", "Purchase details and receipt\n(total, payment and change)", False, 1520, .70, 2)
    left_flow(d, "labor", "2.0", "Service status and\ncompletion details*", True, 1610, .85, 3)
    left_flow(d, "labor", "2.0", "Service job details\nand work instructions*", False, 1720, .95, 3)
    right_flow(d, "2.0", "products-sales", "Available products, quantities\nand selling prices", False, 1010, .10)
    right_flow(d, "2.0", "products-sales", "Stock quantities deducted\nfor recorded sales", True, 1180, .20)
    right_flow(d, "2.0", "product-sales", "Completed sale, items, totals,\npayment details and cashier ID", True, 1320, .42, 1)
    right_flow(d, "2.0", "product-sales", "Sales records and receipt details\n(filtered by cashier when required)", False, 1470, .62, 1)
    right_flow(d, "2.0", "service-sales", "Service description, charge,\ntransaction link and job status*", True, 1610, .80, 2)
    right_flow(d, "2.0", "service-sales", "Existing service-sale\nand completion details*", False, 1795, .96, 2)

    actor(d, "owner-reports", "Admin/Owner", 2050)
    process(d, "3.0", 1940, "Report Generation",
            "Generate sales report\nGenerate inventory report\nPrint or download reports", 255)
    store(d, "products-reports", "D1", 1910, "Product Records",
          "Product details and stock balances\nPrices and low-stock thresholds")
    store(d, "product-sales-reports", "D2", 2120, "Product Sale Records",
          "Transaction dates, sold quantities\nSales amounts and payment details")
    store(d, "service-sales-reports", "D3", 2330, "Service Sale Records*",
          "Recorded service charges, dates\nand completion status")
    left_flow(d, "owner-reports", "3.0", "Report type, date range, filters\nand print/download selection", True, 1940, .25)
    left_flow(d, "owner-reports", "3.0", "Sales and inventory reports\nfor printing or downloading", False, 2170, .80)
    right_flow(d, "3.0", "products-reports", "Inventory data and\ncurrent stock balances", False, 1930, .20)
    right_flow(d, "3.0", "product-sales-reports", "Sales data for the\nselected reporting period", False, 2140, .55, 1)
    right_flow(d, "3.0", "service-sales-reports", "Service-sale data retained\nfrom the previous DFD*", False, 2380, .88, 2)

    actor(d, "owner-backup", "Admin/Owner", 2700)
    process(d, "5.0", 2605, "Database Backup\nand Restore",
            "Create database backup\nRestore database backup\nConfirm admin password for restore", 270)
    store(d, "system-database", "D6", 2590, "System Database",
          "Aggregate of D1-D4 records\nUsed for backup and restoration")
    store(d, "backup-files", "D5", 2870, "Database Backup Files",
          "Saved database snapshots\nSelected backup for restoration")
    left_flow(d, "owner-backup", "5.0", "Backup request or selected restore file\nand administrator password", True, 2610, .28)
    left_flow(d, "owner-backup", "5.0", "Backup file/details or\nrestoration confirmation", False, 2830, .80)
    right_flow(d, "5.0", "system-database", "Current database records\nfor a new backup", False, 2570, .20)
    right_flow(d, "5.0", "system-database", "Validated database records\nrestored from the selected backup", True, 2720, .42)
    right_flow(d, "5.0", "backup-files", "New database backup\nand backup file details", True, 2860, .68, 1)
    right_flow(d, "5.0", "backup-files", "Selected backup contents\nfor validation and restoration", False, 3020, .94, 1)
    return d


NOTES = (
    "Repeated rectangles refer to the same entity/store. Customer and Labor have no login role in the supplied use case.\n"
    "*Labor and D3 service flows are retained from the previous Level 0; they are not covered by the supplied use case.\n"
    "D6 is the aggregate of D1-D4 for backup/restore, not an additional physical database."
)
LEGEND = "Rectangle = external entity   |   Numbered box = process   |   D1-D6 = logical data stores   |   Arrow = data flow"


def write_artifacts(d):
    classic.validate([d])
    expected_entities = {"Admin/Owner", "Cashier", "Customer", "Labor"}
    assert {n["title"] for n in d.nodes.values() if n["kind"] == "external"} == expected_entities
    assert {n["id"] for n in d.nodes.values() if n["kind"] == "process"} == {"1.0", "2.0", "3.0", "4.0", "5.0"}
    for edge in d.edges:
        assert all(0 <= x <= d.w and 0 <= y <= d.h for x, y in edge["points"])

    OUT.mkdir(parents=True, exist_ok=True)
    mxfile = ET.Element("mxfile", host="app.diagrams.net", type="device", pages="1", compressed="false")
    classic.graphxml(mxfile, d)
    root = mxfile.find("diagram/mxGraphModel/root")
    root.find("mxCell[@id='legend']").set("value", LEGEND)
    classic.textcell(root, "scope-notes", NOTES, 50, d.h-150, d.w-100, 75, 17)
    for cell in root.findall("mxCell"):
        geometry = cell.find("mxGeometry")
        if cell.get("id") == "heading":
            cell.set("value", d.title + "\nPaolo Paolo D.A. Matting and Accessories Inventory and Sales System")
            cell.set("style", cell.get("style").replace("fontSize=28", "fontSize=30"))
            geometry.set("height", "90")
    # The native XML must retain valid parents, edge endpoints and unique IDs.
    ids = [cell.get("id") for cell in root.findall("mxCell")]
    assert len(ids) == len(set(ids))
    for cell in root.findall("mxCell"):
        for attr in ("parent", "source", "target"):
            assert cell.get(attr) is None or cell.get(attr) in ids
    target = OUT / f"{STEM}.drawio"
    ET.indent(mxfile)
    ET.ElementTree(mxfile).write(target, encoding="utf-8", xml_declaration=True)
    classic.render(d, STEM)

    # Add identical scope notes and legend to the preview without modifying
    # original rendering helpers or any older diagrams.
    from PIL import Image, ImageDraw
    preview = Image.open(OUT / f"{STEM}.png")
    draw = ImageDraw.Draw(preview)
    scale = 2
    draw.rectangle((0, (d.h-160)*scale, d.w*scale, d.h*scale), fill="white")
    svg_path = OUT / f"{STEM}.svg"
    svg = svg_path.read_text(encoding="utf-8")
    # Replace the old D1-D3 legend from the shared renderer.
    svg = svg.replace("D1–D3 = data stores", "D1-D6 = logical data stores")
    import html
    subtitle = "Paolo Paolo D.A. Matting and Accessories Inventory and Sales System"
    subtitle_font = classic.font(44, bold=True)
    subtitle_x = (d.w*scale-subtitle_font.getlength(subtitle))/2
    draw.text((subtitle_x, 70*scale), subtitle, font=subtitle_font, fill="black")
    additions = [f'<text x="{subtitle_x/scale}" y="92" font-family="Arial, sans-serif" font-size="22" font-weight="700">{html.escape(subtitle)}</text>']
    for index, line in enumerate(NOTES.splitlines()):
        face = classic.font(34)
        x = (d.w*scale - face.getlength(line))/2
        y = (d.h-145 + index*24)*scale
        draw.text((x, y), line, font=face, fill="black")
        additions.append(f'<text x="{x/scale}" y="{y/scale+17}" font-family="Arial, sans-serif" font-size="17" text-anchor="start">{html.escape(line)}</text>')
    face = classic.font(28)
    x = (d.w*scale-face.getlength(LEGEND))/2
    draw.text((x, (d.h-40)*scale), LEGEND, font=face, fill="black")
    svg = svg.replace("</svg>", "\n".join(additions)+"\n</svg>")
    preview.save(OUT / f"{STEM}.png")
    svg_path.write_text(svg, encoding="utf-8")

    with (OUT / f"{STEM}-flows.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["flow_id", "source", "destination", "data"])
        for edge in d.edges:
            writer.writerow([edge["id"], edge["source"], edge["target"], edge["label"].replace("\n", " ")])
    with (OUT / f"{STEM}-use-cases.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["supplied_use_case", "level_0_process", "permitted_actor"])
        writer.writerows(USE_CASES)
    mapping = "\n".join(f"| {name} | {process_id} | {actors} |" for name, process_id, actors in USE_CASES)
    (OUT / f"{STEM}-notes.md").write_text(
        "# Level 0 DFD matched to the supplied use case\n\n"
        "Open `Level-0-DFD-Use-Case-Matched.drawio` for editable native shapes, labels and arrows. "
        "The PNG and SVG are previews of that single Level 0 page. Original artifacts are preserved.\n\n"
        "The Owner is labelled Admin/Owner to match the use case. Customer and Labor remain, "
        "and Cashier is added. Repeated actor/store symbols identify the same entity or logical store.\n\n"
        "## Use-case mapping\n\n| Supplied use case | Level 0 process | Actor |\n"
        "| --- | --- | --- |\n" + mapping + "\n\n"
        "## Retained business scope\n\n"
        "The prior Level 0 included Labor, service jobs and D3 service-sale records. These are retained "
        "at the user's request and marked with an asterisk. The supplied use-case image does not "
        "include service management. These flows describe the previous conceptual business workflow, "
        "not implemented Laravel service functions. Customer and Labor exchange information through "
        "the Owner/Cashier; the use case gives neither a system login. Full documentation alignment "
        "would require adding these service interactions to the use case or explaining their business-only scope.\n\n"
        "D1 Product Records and D2 Product Sale Records are retained; D4 covers user accounts and sessions. "
        "D5 contains backup files. D6 denotes the aggregate database state of D1-D4 for backup and restore, "
        "not a second physical database. Sales read and write stock through D1, so no separate inventory "
        "process-to-sales arrow is needed. Cashier history returns only their own transactions.\n\n"
        "The earlier context and Level 1 pages have not been changed; their older scope is not balanced "
        "with this expanded Level 0. Update those pages before using them together in the final document.\n\n"
        "No application source or database rows were changed. Regenerate with "
        "`scripts/generate-use-case-matched-dfd.py`.\n", encoding="utf-8")
    print(json.dumps({"drawio": str(target), "pages": 1, "processes": 5,
                      "external_entities": sorted(expected_entities), "flows": len(d.edges),
                      "mapped_use_cases": len(USE_CASES)}, indent=2))


if __name__ == "__main__":
    write_artifacts(overview())
