"""Generate editable draw.io DFDs and matching vector/raster previews.

This is documentation only. No database rows, credentials or application files
are modified or included. Store groups describe the current application code.
"""

from __future__ import annotations

import base64
import csv
import html
import json
import math
from pathlib import Path
import urllib.parse
import xml.etree.ElementTree as ET
import zlib

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "dfd"
INK = "#172b45"
LINE = "#425b76"
BLUE = "#2563a6"
MUTED = "#587086"
PURPLE = "#70449a"
GREEN = "#147d6b"
FONT_DIR = Path("C:/Windows/Fonts")

# A DFD uses logical stores, which can group several related physical tables.
STORES = {
    "D1": ("Product catalog & photos", "tbl_Product / tbl_Category / tbl_Status\nImage paths + public/uploads/products files"),
    "D2": ("Stock-in batches", "tbl_Stock_in\nReceived / remaining qty, prices, expiry, condition"),
    "D3": ("Sales & payment records", "tbl_Sale / tbl_Sold_Item / tbl_Payment_Method\nSale lines, totals, tender, change, GCash reference"),
    "D4": ("Accounts, sessions & recovery", "users / sessions / password_reset_requests\nadmin_recovery_codes + recovery-related tables"),
    "D5": ("Backup configuration", "storage/app/backup_settings.json\nMode, time, frequency, retention, folder, timestamps"),
    "D6": ("Local backup files", "Selected folder or storage/app/backups\nSQL Server JSON snapshots + archive folder"),
    "D7": ("Application SQL Server database", "All 21 database tables in the supplied snapshot\nSQL part of D1-D4 + framework tables"),
}


def font(size, bold=False):
    return ImageFont.truetype(str(FONT_DIR / ("arialbd.ttf" if bold else "arial.ttf")), round(size))


def measure(value, size=17, bold=False):
    return max((font(size, bold).getlength(line) for line in value.splitlines()), default=0)


class Diagram:
    def __init__(self, ident, name, title, subtitle, width, height, notes):
        self.id, self.name = ident, name
        self.title, self.subtitle = title, subtitle
        self.w, self.h, self.notes = width, height, notes
        self.nodes = {}
        self.edges = []

    def node(self, ident, kind, x, y, w, h, title, body="", ref=None):
        assert ident not in self.nodes
        self.nodes[ident] = dict(id=ident, kind=kind, x=x, y=y, w=w, h=h,
                                title=title, body=body, ref=ref or ident)
        return ident

    def process(self, ident, x, y, title, body, w=420, h=190):
        return self.node(ident, "process", x, y, w, h, title, body)

    def external(self, ident, x, y, title, body="", ref="E1", w=340, h=100):
        return self.node(ident, "external", x, y, w, h, title, body, ref)

    def store(self, ident, ref, x, y, w=500, h=106):
        title, body = STORES[ref]
        return self.node(ident, "store", x, y, w, h, title, body, ref)

    def point(self, ident, side, fraction=.5):
        n = self.nodes[ident]
        return {"left": (n["x"], n["y"]+n["h"]*fraction),
                "right": (n["x"]+n["w"], n["y"]+n["h"]*fraction),
                "top": (n["x"]+n["w"]*fraction, n["y"]),
                "bottom": (n["x"]+n["w"]*fraction, n["y"]+n["h"])}[side]

    def edge(self, source, target, label, source_side="right", target_side="left",
             sf=.5, tf=.5, via=None, label_at=None, label_w=None, size=17):
        start, end = self.point(source, source_side, sf), self.point(target, target_side, tf)
        label_w = label_w or measure(label, size) + 24
        label_h = len(label.splitlines())*(size+5)+10
        if via is None:
            if source_side in ("right", "left") and target_side in ("right", "left"):
                if label_at is not None and "external" in (self.nodes[source]["kind"], self.nodes[target]["kind"]):
                    direction = 1 if end[0] > start[0] else -1
                    bridge_y = label_at[1]+label_h+8 if self.nodes[source]["kind"] == "external" else label_at[1]-8
                    left, right = start[0]+25*direction, end[0]-25*direction
                    via = [(left,start[1]), (left,bridge_y), (right,bridge_y), (right,end[1])]
                else:
                    mid = (start[0]+end[0])/2
                    via = [(mid, start[1]), (mid, end[1])]
            else:
                mid = (start[1]+end[1])/2
                via = [(start[0], mid), (end[0], mid)]
        points = [start, *via, end]
        compact = []
        for p in points:
            if not compact or p != compact[-1]:
                compact.append(p)
        if label_at is None:
            horizontal = [(a, b) for a, b in zip(compact, compact[1:]) if a[1] == b[1]]
            a, b = max(horizontal, key=lambda ab: abs(ab[1][0]-ab[0][0]))
            label_at = ((a[0]+b[0])/2-label_w/2, a[1]-label_h-9)
        self.edges.append(dict(id=f"{self.id}-flow-{len(self.edges)+1:02}", source=source, target=target,
                               source_side=source_side, target_side=target_side, sf=sf, tf=tf,
                               label=label, points=compact, lx=label_at[0], ly=label_at[1],
                               lw=label_w, lh=label_h, size=size))


def pair(diagram, process, store, write, read, pf1=.3, pf2=.7, label_x=1240, label_w=390):
    """Separated directional arrows; each label sits in the clear horizontal gap."""
    n = diagram.nodes[store]
    pn = diagram.nodes[process]
    outgoing_x, incoming_x = pn["x"]+pn["w"]+20, pn["x"]+pn["w"]+30
    if write:
        start = diagram.point(process, "right", pf1)
        bridge_y = n["y"]+32
        diagram.edge(process, store, write, sf=pf1, tf=.25,
                     via=[(outgoing_x,start[1]), (outgoing_x,bridge_y),
                          (n["x"]-30,bridge_y), (n["x"]-30,n["y"]+n["h"]*.25)],
                     label_at=(label_x,n["y"]-28), label_w=label_w, size=16)
    if read:
        end = diagram.point(process, "right", pf2)
        bridge_y = n["y"]+58
        diagram.edge(store, process, read, source_side="left", target_side="right", sf=.75, tf=pf2,
                     via=[(n["x"]-15,n["y"]+n["h"]*.75), (n["x"]-15,bridge_y),
                          (incoming_x,bridge_y), (incoming_x,end[1])],
                     label_at=(label_x,n["y"]+65), label_w=label_w, size=16)


def main_level_zero():
    d = Diagram("level-0", "Level 0 - Whole system", "Figure 3. Level 0 Data Flow Diagram",
                "Paolo Paolo D.A. Matting & Accessories | Current product-based application | Updated 10 October 2026",
                2260, 2730,
                ["Numbering: 1.0 inventory, 2.0 POS, 3.0 reports, 4.0 accounts, 5.0 backups. Detailed child processes are on the following pages.",
                 "Repeated E/D symbols refer to the same entity or logical data store. D7 is the SQL database aggregate, not another physical database.",
                 "Customer order and tender details are entered by a POS operator. The customer has no separate login or customer master table.",
                 "This DFD shows information, not movement of money or products. Labor and service-sale entities are absent from the current application."])
    for y, title in [(160, "INVENTORY"), (610, "POINT OF SALE"), (1110, "DASHBOARD & REPORTS"),
                     (1705, "ACCESS & SECURITY"), (2115, "BACKUP & RESTORE")]:
        d.node(f"band-{y}", "band", 40, y, 2180, 0, title)

    # 1.0: product and category records are distinct from received stock batches.
    p = d.process("1.0", 770, 270, "Maintain Inventory & Receive Stock",
                  "New / existing product; category maintenance\nPhoto upload; archive / restore\nReceive / correct batch; expiry & condition\nFilter, sort, derive sellable stock", h=230)
    e = d.external("inventory-admin", 60, 300, "Owner / Administrator", "Authenticated admin", w=330, h=105)
    d.edge(e, p, "Product/category changes, photos,\nshipment details, batch edits, filters", sf=.22, tf=.24,
           label_at=(425, 275), label_w=315, size=17)
    d.edge(p, e, "Catalog / stock-in log, filtered inventory,\nsaved changes or validation errors", source_side="left", target_side="right", sf=.78, tf=.8,
           label_at=(425, 450), label_w=315, size=17)
    s1 = d.store("inventory-catalog", "D1", 1680, 230)
    s2 = d.store("inventory-batches", "D2", 1680, 405)
    staff = d.store("inventory-staff", "D4", 1680, 555)
    pair(d, p, s1, "Product / category / status updates;\nphoto files and stored image paths", "Existing catalog, categories,\nstatus and image references", .12, .34)
    pair(d, p, s2, "New / corrected batch records;\nlogged-in receiving user ID", "Batch quantities, prices,\nexpiration dates and conditions", .62, .87)
    pair(d, p, staff, None, "Receiving staff names / IDs\nfor receiving-log filters and display", pf2=.97)

    # 2.0: only actual supported tender methods; customer details are relayed by staff.
    p = d.process("2.0", 770, 780, "Process Product Sales (POS)",
                  "Cash / GCash; validate cart and tender\nGood, unexpired, remaining batches only\nFIFO by ascending batch ID; stock locks\nSave sale + sale lines; deduct remaining qty\nReceipt / cash change / GCash reference", h=245)
    e = d.external("pos-operator", 60, 835, "POS Operator", "Admin / Cashier / Employee\nCustomer details entered at POS", "E2", w=330, h=120)
    d.edge(e, p, "Cart/product IDs and quantities,\nmethod, tender, GCash reference,\nselling user ID (admin override)", sf=.2, tf=.25,
           label_at=(425, 790), label_w=315, size=17)
    d.edge(p, e, "Sellable catalog, checkout result,\nreceipt details, total and change", source_side="left", target_side="right", sf=.78, tf=.8,
           label_at=(425, 985), label_w=315, size=17)
    s1 = d.store("pos-catalog", "D1", 1680, 705)
    s2 = d.store("pos-batches", "D2", 1680, 880)
    s3 = d.store("pos-sales", "D3", 1680, 1055)
    s4 = d.store("pos-users", "D4", 1680, 1180)
    pair(d, p, s1, None, "Product identity, category, status\nand catalog photo references", pf2=.09)
    pair(d, p, s2, "Reduced Remaining_Quantity\nfor allocated FIFO batches", "Sellable quantity and latest retail price;\noldest eligible batch IDs", .4, .63)
    pair(d, p, s3, "Sale header, sold items, tender,\nchange, staff ID, GCash reference", "Cash / GCash payment lookup;\nsaved sale and receipt records", .79, .95)
    pair(d, p, s4, None, "Selling staff identity / active status;\nreceipt-access user and staff display name", pf2=.99)

    # 3.0: separate admin reporting scope from an employee's own transactions.
    p = d.process("3.0", 770, 1350, "Produce Dashboard & Reports",
                  "Selected date / period / cashier filters\nSales totals and top / bottom 5 products\nCurrent low / out-of-stock indicators\nInventory, delivery and sales reports\nPrint / PDF; own-transaction CSV export", h=260)
    e1 = d.external("report-admin", 60, 1300, "Owner / Administrator", "All business / inventory reports", w=330, h=100)
    e2 = d.external("report-operator", 60, 1580, "Cashier / Employee", "Own transactions only", "E2", w=330, h=100)
    d.edge(e1, p, "Date/period, report type, inventory\nfilters, cashier and output choice", sf=.22, tf=.18,
           label_at=(425, 1300), label_w=315, size=17)
    d.edge(p, e1, "Dashboard totals, stock indicators,\nrankings and printable/PDF reports", source_side="left", target_side="right", sf=.38, tf=.82,
           label_at=(425, 1440), label_w=315, size=17)
    d.edge(e2, p, "Own-sale search, period, method\nand print / PDF / CSV request", sf=.22, tf=.6,
           label_at=(425, 1530), label_w=315, size=17)
    d.edge(p, e2, "Own filtered transactions\nand requested report/export", source_side="left", target_side="right", sf=.89, tf=.82,
           label_at=(425, 1660), label_w=315, size=17)
    for ident, ref, y, read, port in [
        ("report-catalog", "D1", 1310, "Product / category / status descriptions", .08),
        ("report-stock", "D2", 1460, "Current sellable balances and valuation;\nreceived batches for selected dates", .32),
        ("report-sales", "D3", 1610, "Sales, quantities sold, payment labels\nand totals for selected dates", .6),
        ("report-users", "D4", 1760, "Cashier / receiving staff names\nand user IDs for report scope", .89)]:
        s = d.store(ident, ref, 1680, y)
        pair(d, p, s, None, read, pf2=port)

    p = d.process("4.0", 770, 1860, "Authenticate & Manage Accounts",
                  "Login / logout; active user and role checks\nAdmin creates / enables / disables employees\nAccount / password changes\nEmployee reset approval; admin recovery codes", h=230)
    e = d.external("security-users", 60, 1895, "Admin / Cashier / Employee", "Admin approval for employee resets", "E1+E2", w=330, h=115)
    d.edge(e, p, "Credentials, account changes,\nemployee management and recovery\nrequests / codes", sf=.2, tf=.23,
           label_at=(425, 1865), label_w=315, size=17)
    d.edge(p, e, "Session and permitted access,\naccount status, approval or\nrecovery result", source_side="left", target_side="right", sf=.8, tf=.82,
           label_at=(425, 2040), label_w=315, size=17)
    s = d.store("security-accounts", "D4", 1680, 1910)
    pair(d, p, s, "User / session updates; hashed recovery\ncodes, approvals and usage timestamps", "User identity / password hash / role,\nactive status and recovery records", .3, .72)

    p = d.process("5.0", 770, 2320, "Back Up & Restore Database",
                  "Manual or scheduled snapshot; due-time check\nConfigured folder; local fallback; backup lock\nRetention / archive / download / recovery\nValidate snapshot and restore SQL rows\nOptional cloud upload via configured web app", h=245)
    e = d.external("backup-admin", 60, 2175, "Owner / Administrator", "Backup settings / manual operations", w=330, h=100)
    timer = d.external("backup-timer", 60, 2490, "Application Scheduler", "Minute tick in Asia/Manila", "E3", w=330, h=82)
    d.edge(e, p, "Mode/time/frequency/folder/retention,\nbackup/restore/download/file request", sf=.2, tf=.22,
           label_at=(425, 2190), label_w=315, size=17)
    d.edge(p, e, "Backup list/file, save/restore result,\nlocal path and optional upload status", source_side="left", target_side="right", sf=.58, tf=.85,
           label_at=(425, 2375), label_w=315, size=17)
    d.edge(timer, p, "Current date/time for scheduled backup", sf=.4, tf=.91,
           label_at=(425, 2490), label_w=315, size=17)
    for ident, ref, y, write, read, a, b in [
        ("backup-settings", "D5", 2180, "Validated settings; last successful\nbackup and automatic-backup timestamps", "Schedule, folder, retention\nand optional upload preferences", .09, .23),
        ("backup-files", "D6", 2330, "Snapshot files and archive /\nretention-related file changes", "Existing backup payloads\nand active / archived file list", .43, .58),
        ("backup-database", "D7", 2480, "Validated restored table rows\nin a SQL transaction", "Table / column metadata and row\ndata for snapshot or restore validation", .78, .94)]:
        s = d.store(ident, ref, 1680, y)
        pair(d, p, s, write, read, a, b)
    # Cloud is expanded on the backup page; include its balanced interface here.
    cloud = d.external("main-cloud", 1150, 2605, "Google Drive upload web app", "Optional; configured and online only", "E4", w=465, h=75)
    d.edge(p, cloud, "Snapshot file upload", "bottom", "left", sf=.75, tf=.3,
           via=[(1085, 2627), (1120, 2627)], label_at=(840, 2600), label_w=260, size=15)
    d.edge(cloud, p, "Upload result / file ID", "left", "bottom", sf=.75, tf=.93,
           via=[(1130, 2661), (1160, 2661)], label_at=(840, 2640), label_w=275, size=15)
    # Notes are kept in a separate guide so the overview stays within a fixed canvas.
    return d


def inventory_level_one():
    d = Diagram("inventory-detail", "Level 1 - Inventory", "Figure 3a. Detailed Inventory Data Flow",
                "Decomposition of 1.0 | Product/category changes, shipment receiving, safe batch edits and automatic filters",
                2280, 2150,
                ["New-product receiving creates the catalog entry and first stock batch together. Receiving staff is the authenticated admin; it is not selected.",
                 "Batch edit: used quantity = received - remaining; edited received quantity cannot be below used quantity. New remaining = edited received - used.",
                 "Sellable stock = SUM(remaining) for Good batches with remaining > 0 and no expiry or expiry >= today. Low stock: 1-5; out of stock: 0.",
                 "Catalog prices use the newest sellable batch. Photos are real files; D1 stores their paths in Image / Images. Archived products are excluded from POS."])
    admin = d.external("inv-input", 70, 230, "Owner / Administrator", "Product / category / shipment / edit entry", w=350, h=110)
    v = d.process("1.1", 70, 640, "Validate Inventory Request",
                  "New / existing product and valid category\nName, image type/size, quantity, prices\nBatch ownership, date and condition\nAuthenticated receiving user ID", w=420, h=205)
    p2 = d.process("1.2", 820, 235, "Maintain Catalog & Photos",
                   "Create / edit product and category\nArchive / restore product or category\nSave / remove photo files and image paths\nUse product status and category references", h=205)
    p3 = d.process("1.3", 820, 665, "Record Received Shipment",
                   "Resolve new / existing Product_ID\nSave quantity, cost, retail, expiry, condition\nRemaining_Quantity starts at Quantity\nUser_ID = authenticated admin", h=205)
    p4 = d.process("1.4", 820, 1100, "Correct Existing Stock Batches",
                   "Lock product and selected owned batches\nPreserve quantity already sold / used\nUpdate received / remaining quantity safely\nUpdate prices, expiry and condition", h=205)
    d.edge(admin, v, "Product/category changes, uploaded photos,\nshipment or batch-correction data", "bottom", "top", sf=.28, tf=.23,
           via=[(168, 490), (166.6, 490)], label_at=(40, 420), label_w=420)
    d.edge(v, admin, "Validation error details", "top", "bottom", sf=.78, tf=.78,
           via=[(397.6, 570), (343, 570)], label_at=(260, 550), label_w=245)
    d.edge(v, p2, "Validated product / category /\nphoto changes", sf=.17, tf=.65,
           via=[(650, 674.85), (650, 368.25)], label_at=(495, 520), label_w=280)
    d.edge(v, p3, "Validated shipment fields,\nproduct selection and processor ID", sf=.48, tf=.3,
           label_at=(490, 718), label_w=300)
    d.edge(v, p4, "Validated batch IDs and\nrequested corrections", sf=.85, tf=.35,
           via=[(625, 814.25), (625, 1171.75)], label_at=(485, 980), label_w=285)
    d.edge(p2, p3, "New or existing product ID\n(for new-product receiving)", "bottom", "top", sf=.5, tf=.5,
           label_at=(870, 505), label_w=325)
    catalog = d.store("inv-catalog-write", "D1", 1720, 290)
    pair(d, p2, catalog, "Saved product/category/status changes;\nphoto files and image references", "Current product / category / status\nand existing photo references", .23, .8, label_x=1280, label_w=405)
    batches = d.store("inv-batch-new", "D2", 1720, 715)
    pair(d, p3, batches, "New received batch record\nwith remaining qty and receiving user", None, .5, label_x=1280, label_w=405)
    batch_edit = d.store("inv-batch-edit", "D2", 1720, 1150)
    pair(d, p4, batch_edit, "Corrected batch record;\nremaining qty preserves prior consumption", "Owned batch IDs, received / remaining\nquantity, cost, retail, expiry, condition", .25, .8, label_x=1280, label_w=405)
    c_lookup = d.store("inv-catalog-check", "D1", 70, 1060, w=500)
    d.edge(c_lookup, v, "Existing product and active-category IDs", "top", "bottom", sf=.5, tf=.5,
           via=[(320, 950), (280, 950)], label_at=(70, 955), label_w=430)

    admin2 = d.external("inv-filter", 70, 1510, "Owner / Administrator", "Inventory / receiving-log view", w=350, h=115)
    p5 = d.process("1.5", 820, 1470, "Filter & Display Stock",
                   "Search, category, status/tab, stock level, sort\nReceiving-log product / staff filters\nCalculate sellable quantity and current prices\nShow inventory / stock-in records", h=205)
    d.edge(admin2, p5, "Chosen filter/search/sort option\n(automatically submitted by the UI)", sf=.2, tf=.23,
           label_at=(465, 1480), label_w=305)
    d.edge(p5, admin2, "Filtered inventory / delivery log,\nlow-stock and out-of-stock status", "left", "right", sf=.82, tf=.82,
           label_at=(465, 1638), label_w=305)
    cat_read = d.store("inv-view-catalog", "D1", 1720, 1440)
    stock_read = d.store("inv-view-batches", "D2", 1720, 1660)
    pair(d, p5, cat_read, None, "Catalog names / categories / status\nand photo paths/files", pf2=.2, label_x=1280, label_w=405)
    pair(d, p5, stock_read, None, "Remaining qty, expiry, condition,\nprices and receiving staff ID", pf2=.8, label_x=1280, label_w=405)
    staff_read = d.store("inv-view-staff", "D4", 1720, 1870)
    pair(d, p5, staff_read, None, "Receiving user IDs and display names\nfor staff filters and delivery logs", pf2=.97, label_x=1280, label_w=405)
    return d


def pos_level_one():
    d = Diagram("pos-detail", "Level 1 - POS & FIFO", "Figure 3b. Detailed Sales and FIFO Data Flow",
                "Decomposition of 2.0 | Customer details entered by an authenticated Admin, Cashier or Employee",
                2280, 2440,
                ["2.3 and 2.4 run inside a database transaction. Batches are locked, then sold-item records and remaining stock are committed together.",
                 "FIFO consumes eligible stock by ascending stock-in ID. Selling price is the newest sellable batch's retail price; FIFO determines stock consumption.",
                 "Cash tender must cover the total; change = tender - total. GCash amount must equal total and a reference is required; there is no payment-gateway verification.",
                 "Receipts are visible to the admin or the user who processed that sale. The customer receives receipt information through the POS operator."])
    p1 = d.process("2.1", 800, 245, "Present Catalog & Build Cart",
                   "Show non-archived products with sellable stock\nRead category, photo, current price and stock\nOperator selects product and quantity\nProduce draft cart / selected item IDs", h=205)
    p2 = d.process("2.2", 800, 670, "Validate Checkout Fields",
                   "Required item list / valid product IDs\nNumeric quantity >= 1; nonnegative tender\nPayment method must be Cash or GCash\nGCash reference required when selected\nActive selling user; only admin can override", h=230)
    p3 = d.process("2.3", 800, 1080, "Check Stock & Allocate FIFO",
                   "Lock Good, unexpired batches with remaining > 0\nCheck total sellable quantity for each item\nAllocate oldest batch IDs first\nCalculate line totals using current retail price", h=205)
    p4 = d.process("2.4", 800, 1535, "Validate Tender & Commit Sale",
                   "Compare tender with server-calculated total\nResolve authenticated selling user / admin override\nSave sale header and sold-item records\nDeduct FIFO quantities from stock batches", h=225)
    p5 = d.process("2.5", 800, 1990, "Return Sale Result & Receipt",
                   "Return invoice / sale ID, items, total and date\nDisplay staff, method and GCash reference\nReturn received amount and cash change\nAuthorize access to requested saved receipt", h=205)
    e1 = d.external("pos-cart", 60, 270, "POS Operator", "Admin / Cashier / Employee", "E2", w=340, h=110)
    d.edge(e1, p1, "Product selection and quantities", sf=.2, tf=.25, label_at=(445, 250), label_w=315)
    d.edge(p1, e1, "Sellable catalog, prices,\nstock and photos", "left", "right", sf=.8, tf=.8, label_at=(440, 405), label_w=315)
    s1 = d.store("pos1-catalog", "D1", 1720, 205)
    s2 = d.store("pos1-stock", "D2", 1720, 385)
    pair(d, p1, s1, None, "Product/category/status records\nand photo references", pf2=.22, label_x=1260, label_w=420)
    pair(d, p1, s2, None, "Eligible quantity and newest\nsellable batch retail price", pf2=.82, label_x=1260, label_w=420)
    d.edge(p1, p2, "Draft cart + selected product IDs and quantities", "bottom", "top", label_at=(780, 540), label_w=485)
    e2 = d.external("pos-payment", 60, 700, "POS Operator", "Payment entry / checkout submission", "E2", w=340, h=110)
    d.edge(e2, p2, "Cart, payment method, tender,\nGCash reference if applicable", sf=.2, tf=.25, label_at=(445, 670), label_w=315)
    d.edge(p2, e2, "Missing / invalid checkout-field errors", "left", "right", sf=.82, tf=.82, label_at=(440, 840), label_w=325)
    methods = d.store("pos2-methods", "D3", 1720, 725)
    pair(d, p2, methods, None, "Cash / GCash lookup IDs\nfrom tbl_Payment_Method", pf2=.65, label_x=1260, label_w=420)
    actor = d.store("pos2-actor", "D4", 60, 925)
    d.edge(actor, p2, "Selling user IDs,\nactive state and\ncurrent operator role", "right", "left", sf=.35, tf=.97,
           via=[(590,962.1), (590,900), (775,900), (775,893.1)],
           label_at=(600,912), label_w=175, size=15)
    d.edge(p2, p3, "Valid item quantities and payment details", "bottom", "top", label_at=(815, 950), label_w=415)
    fifo = d.store("pos3-fifo", "D2", 1720, 1130)
    pair(d, p3, fifo, None, "Eligible locked stock rows, remaining qty,\nbatch IDs and current price", pf2=.55, label_x=1260, label_w=420)
    e3 = d.external("pos-stock-error", 60, 1150, "POS Operator", "Checkout response", "E2", w=340, h=100)
    d.edge(p3, e3, "Insufficient-stock error\nand available quantity", "left", "right", sf=.6, tf=.5, label_at=(440, 1140), label_w=330)
    d.edge(p3, p4, "Calculated sale/line totals, FIFO allocations,\nvalidated payment fields and selling user", "bottom", "top", label_at=(775, 1380), label_w=490)
    sales = d.store("pos4-sales", "D3", 1720, 1480)
    updated = d.store("pos4-stock", "D2", 1720, 1695)
    pair(d, p4, sales, "tbl_Sale: staff, method, date, tender, total, change, reference\ntbl_Sold_Item: product, sale ID, quantity and line total", None, .25, label_x=1250, label_w=440)
    pair(d, p4, updated, "Remaining_Quantity minus allocated units\nfor each consumed stock-in batch", None, .8, label_x=1260, label_w=420)
    e4 = d.external("pos-tender-error", 60, 1600, "POS Operator", "Checkout response", "E2", w=340, h=100)
    d.edge(p4, e4, "Insufficient cash / GCash-total mismatch\nor invalid selling-user/session result", "left", "right", sf=.55, tf=.5, label_at=(445, 1570), label_w=315)
    d.edge(p4, p5, "Committed sale ID and checkout result", "bottom", "top", label_at=(815, 1870), label_w=415)
    e5 = d.external("pos-receipt", 60, 2040, "POS Operator", "Receipt is issued to the customer", "E2", w=340, h=110)
    d.edge(e5, p5, "Saved receipt request / sale ID", sf=.2, tf=.25, label_at=(445, 2000), label_w=315)
    d.edge(p5, e5, "Receipt / invoice data, total, tender,\nchange and payment confirmation details", "left", "right", sf=.82, tf=.82, label_at=(445, 2175), label_w=315)
    sale_read = d.store("pos5-sale-read", "D3", 1720, 1950)
    staff_read = d.store("pos5-user-read", "D4", 1720, 2140)
    pair(d, p5, sale_read, None, "Saved sale, sold lines and\npayment-method name", pf2=.2, label_x=1260, label_w=420)
    pair(d, p5, staff_read, None, "Selling user name / role / ID\nand receipt-access identity", pf2=.83, label_x=1260, label_w=420)
    return d


def backup_level_one():
    d = Diagram("backup-detail", "Level 1 - Backup & restore", "Figure 3c. Detailed Backup and Restore Data Flow",
                "Decomposition of 5.0 | Local SQL Server snapshots, configured schedule, optional cloud copy and validated restore",
                2280, 2610,
                ["Automatic backups require the application scheduler to be running. It checks every minute; configured time and frequency use Asia/Manila.",
                 "SQL Server backups are application JSON snapshots of table names, columns and rows. Restore validates these against the existing database schema.",
                 "Snapshot creation writes a .partial file and renames it after success. Backup timestamps are updated only after the local snapshot succeeds.",
                 "Product image files and backup_settings.json are outside the database snapshot. Cloud upload is optional; a failed upload retains the local backup."])
    p1 = d.process("5.1", 800, 235, "Save Backup Configuration",
                   "Validate mode / time / frequency / retention\nChoose server folder using native picker\nValidate selected directory; save preferences\nPreserve latest backup timestamps", h=205)
    a1 = d.external("backup-config-admin", 60, 275, "Owner / Administrator", "Configuration entry", w=340, h=105)
    d.edge(a1, p1, "Mode, time, frequency, folder, retention,\noptional cloud-upload preference", sf=.2, tf=.25, label_at=(445, 240), label_w=315)
    d.edge(p1, a1, "Selected folder, saved settings\nor validation / lock error", "left", "right", sf=.8, tf=.8, label_at=(445, 400), label_w=315)
    s1 = d.store("backup-config-store", "D5", 1720, 285)
    pair(d, p1, s1, "Validated configuration changes", "Current settings and latest\nbackup timestamps", .25, .8, label_x=1260, label_w=420)

    p2 = d.process("5.2", 800, 655, "Check Schedule & Acquire Lock",
                   "Automatic mode and configured time reached?\nDaily / weekly / monthly interval due?\nUse last successful automatic-backup date\nAcquire exclusive backup lock; retry on failure", h=205)
    timer = d.external("backup-scheduler", 60, 710, "Application Scheduler", "Minute tick; Asia/Manila", "E3", w=340, h=100)
    d.edge(timer, p2, "Current application date and time", sf=.5, tf=.5, label_at=(445, 690), label_w=315)
    s2 = d.store("backup-due-settings", "D5", 1720, 705)
    pair(d, p2, s2, None, "Automatic mode, backup time, frequency\nand last automatic-backup timestamp", pf2=.6, label_x=1260, label_w=420)

    p3 = d.process("5.3", 800, 1095, "Write Local Database Snapshot",
                   "Resolve writable destination or local fallback\nRead all SQL table/column/row data\nWrite .partial JSON, then finalize filename\nPersist successful backup timestamps", h=225)
    a3 = d.external("backup-manual-admin", 60, 1140, "Owner / Administrator", "Manual backup request", w=340, h=100)
    d.edge(a3, p3, "Manual backup request", sf=.25, tf=.27, label_at=(445, 1120), label_w=315)
    d.edge(p2, p3, "Due backup request + exclusive lock", "bottom", "top", label_at=(815, 950), label_w=415)
    db = d.store("backup-snapshot-db", "D7", 1720, 1040)
    files = d.store("backup-snapshot-files", "D6", 1720, 1225)
    config3 = d.store("backup-snapshot-config", "D5", 60, 1370, w=505)
    pair(d, p3, db, None, "All SQL tables: names, columns, rows", pf2=.12, label_x=1260, label_w=420)
    pair(d, p3, files, "Finalized backup_p7db_*.json snapshot", None, .78, label_x=1260, label_w=420)
    d.edge(config3, p3, "Folder, retention and\nupload preferences", "right", "left", sf=.25, tf=.6,
           via=[(650, 1396.5), (650, 1230)], label_at=(565, 1280), label_w=215, size=15)
    d.edge(p3, config3, "last_backup_at;\nlast_automatic_backup_at\n(on automatic success)", "left", "right", sf=.92, tf=.8,
           via=[(715, 1302), (715, 1454.8)], label_at=(565, 1460), label_w=225, size=15)

    p4 = d.process("5.4", 800, 1610, "Apply Retention & Optional Upload",
                   "Apply configured retention to old local backups\nIf enabled, send snapshot to configured web app\nKeep local backup when cloud upload fails\nReturn local path and backup/upload status", h=215)
    d.edge(p3, p4, "Successful local filename +\nretention and upload configuration", "bottom", "top", label_at=(830, 1450), label_w=385)
    a4 = d.external("backup-status-admin", 60, 1660, "Owner / Administrator", "Backup result", w=340, h=100)
    d.edge(p4, a4, "Backup filename, local path,\nsuccess/failure and optional cloud result", "left", "right", sf=.55, tf=.5, label_at=(445, 1630), label_w=315)
    f4 = d.store("backup-retention-files", "D6", 1720, 1545)
    pair(d, p4, f4, "Retention-related local file changes", "Snapshot bytes / filename and\nexisting backup-file dates", .2, .48, label_x=1260, label_w=420)
    drive = d.external("backup-drive", 1720, 1770, "Google Drive upload web app", "Optional destination; configured endpoint", "E4", w=500, h=105)
    d.edge(p4, drive, "Filename, MIME type, snapshot content\n(+ configured upload authentication)", sf=.72, tf=.25,
           label_at=(1260, 1720), label_w=420, size=16)
    d.edge(drive, p4, "Upload success/failure and cloud file ID", "left", "right", sf=.8, tf=.95,
           label_at=(1260, 1825), label_w=420, size=16)

    p5 = d.process("5.5", 800, 2125, "Manage Files & Restore Snapshot",
                   "List / download / archive / recover / purge files\nSelect existing snapshot or admin-uploaded JSON\nValidate format, table/column names and row values\nRestore rows with identity / constraint handling", h=225)
    a5 = d.external("backup-restore-admin", 60, 2160, "Owner / Administrator", "Backup file management / restore", w=340, h=115)
    d.edge(a5, p5, "File action, selected filename,\nor uploaded snapshot for restore", sf=.22, tf=.25, label_at=(445, 2130), label_w=315)
    d.edge(p5, a5, "Backup list/download; file-action\nor restore result / validation errors", "left", "right", sf=.85, tf=.82, label_at=(445, 2300), label_w=315)
    f5 = d.store("backup-restore-files", "D6", 1720, 2030)
    db5 = d.store("backup-restore-db", "D7", 1720, 2300)
    pair(d, p5, f5, "Archive / recovery / purge file changes", "Active / archived file list\nand selected snapshot payload", .12, .4, label_x=1260, label_w=420)
    pair(d, p5, db5, "Validated restored rows\n(transactional replacement)", "Existing table/column metadata\nfor compatibility validation", .69, .95, label_x=1260, label_w=420)
    return d


def style(**values):
    return "".join(f"{k}={v};" for k, v in values.items())


def cell(root, ident, value, styling, x, y, w, h, parent="1", **extras):
    c = ET.SubElement(root, "mxCell", id=ident, value=value, style=styling,
                      vertex="1", parent=parent, **extras)
    ET.SubElement(c, "mxGeometry", x=str(x), y=str(y), width=str(w), height=str(h), **{"as": "geometry"})
    return c


def text_cell(root, ident, value, x, y, w, h, size, color=INK, bold=False, parent="1", align="center", bg="none"):
    return cell(root, ident, value, style(shape="text", html="0", whiteSpace="wrap", overflow="hidden",
                fillColor=bg, strokeColor="none", fontFamily="Arial", fontSize=size, fontColor=color,
                fontStyle="1" if bold else "0", align=align, verticalAlign="middle", spacing="0"),
                x, y, w, h, parent, connectable="0")


def port(side, fraction):
    return {"left": (0, fraction), "right": (1, fraction), "top": (fraction, 0), "bottom": (fraction, 1)}[side]


def xml_page(mxfile, d):
    page = ET.SubElement(mxfile, "diagram", id=d.id, name=d.name)
    model = ET.SubElement(page, "mxGraphModel", dx="2200", dy="1500", grid="0", gridSize="10", guides="1",
                           tooltips="1", connect="1", arrows="1", fold="1", page="1", pageScale="1",
                           pageWidth=str(d.w), pageHeight=str(d.h), math="0", shadow="0",
                           background="#ffffff", adaptiveColors="none")
    root = ET.SubElement(model, "root")
    ET.SubElement(root, "mxCell", id="0")
    ET.SubElement(root, "mxCell", id="1", parent="0")
    text_cell(root, "page-title", d.title, 40, 30, d.w-80, 45, 32, bold=True, align="left")
    text_cell(root, "page-subtitle", d.subtitle, 40, 83, d.w-80, 33, 18, MUTED, align="left")
    text_cell(root, "page-key", "RECTANGLE = external entity   |   ROUNDED BOX = process   |   Dn OPEN STORE = persistent data   |   ARROW = named data flow   |   repeated E/D = same entity/store",
              40, 124, d.w-80, 30, 16, MUTED, align="left")
    for e in d.edges:
        ex, ey = port(e["source_side"], e["sf"])
        ix, iy = port(e["target_side"], e["tf"])
        edge = ET.SubElement(root, "mxCell", id=e["id"], value="", edge="1", parent="1",
                             source=e["source"], target=e["target"],
                             style=style(noEdgeStyle="1", rounded="0", html="0", strokeColor=LINE,
                                         strokeWidth="2", endArrow="classic", endFill="1", endSize="10",
                                         startArrow="none", exitX=ex, exitY=ey, entryX=ix, entryY=iy,
                                         exitPerimeter="0", entryPerimeter="0", jumpStyle="gap", jumpSize="8"))
        g = ET.SubElement(edge, "mxGeometry", relative="1", **{"as": "geometry"})
        points = ET.SubElement(g, "Array", **{"as": "points"})
        for x, y in e["points"][1:-1]:
            ET.SubElement(points, "mxPoint", x=str(x), y=str(y))
    for n in d.nodes.values():
        ident, x, y, w, h = n["id"], n["x"], n["y"], n["w"], n["h"]
        kind = n["kind"]
        if kind == "band":
            text_cell(root, ident, n["title"], x, y, w, 25, 16, BLUE, True, align="left")
            continue
        cell(root, ident, "", style(shape="rectangle", fillColor="none", strokeColor="none", container="1", recursiveResize="1"), x, y, w, h)
        if kind == "process":
            cell(root, ident+"-box", "", style(shape="rectangle", rounded="1", arcSize="9", fillColor="#f0f6fd",
                      strokeColor=BLUE, strokeWidth="2"), 0, 0, w, h, ident, connectable="0")
            text_cell(root, ident+"-number", n["ref"], 15, 12, w-30, 32, 23, BLUE, True, ident)
            text_cell(root, ident+"-title", n["title"], 12, 50, w-24, 45, 22, INK, True, ident)
            text_cell(root, ident+"-body", n["body"], 14, 105, w-28, h-120, 17, MUTED, parent=ident)
        elif kind == "external":
            cell(root, ident+"-box", "", style(shape="rectangle", fillColor="#f7f1fc", strokeColor=PURPLE, strokeWidth="2"),
                 0, 0, w, h, ident, connectable="0")
            text_cell(root, ident+"-title", n["title"], 8, 10, w-16, 33, 21, PURPLE, True, ident)
            text_cell(root, ident+"-body", n["body"], 10, 49, w-20, h-57, 16, MUTED, parent=ident)
        elif kind == "store":
            # Parallel bars plus a numbered left partition: native Gane-Sarson store.
            cell(root, ident+"-paper", "", style(shape="rectangle", fillColor="#eff8f5", strokeColor="none"),
                 0, 0, w, h, ident, connectable="0")
            for k, bx, by, bw, bh in [("top",0,0,w,2), ("bottom",0,h-2,w,2), ("left",0,0,2,h), ("divider",65,0,2,h)]:
                cell(root, ident+"-"+k, "", style(shape="rectangle", fillColor=GREEN, strokeColor="none"),
                     bx, by, bw, bh, ident, connectable="0")
            text_cell(root, ident+"-ref", n["ref"], 6, 0, 52, h, 24, GREEN, True, ident)
            text_cell(root, ident+"-title", n["title"], 78, 7, w-91, 34, 20, INK, True, ident, align="left")
            text_cell(root, ident+"-body", n["body"], 78, 49, w-91, h-55, 15, MUTED, parent=ident, align="left")
    # Native labels remain editable and move together with their connectors.
    for e in d.edges:
        c = text_cell(root, e["id"]+"-label", e["label"], 0, 0, e["lw"], e["lh"], e["size"],
                      LINE, parent=e["id"], bg="#ffffff")
        g = c.find("mxGeometry")
        g.set("relative", "1")
        total = sum(math.dist(a,b) for a,b in zip(e["points"],e["points"][1:]))
        walked = 0
        midpoint = e["points"][0]
        for a,b in zip(e["points"],e["points"][1:]):
            seg = math.dist(a,b)
            if walked+seg >= total/2:
                ratio = (total/2-walked)/seg if seg else 0
                midpoint = (a[0]+ratio*(b[0]-a[0]), a[1]+ratio*(b[1]-a[1]))
                break
            walked += seg
        ET.SubElement(g, "mxPoint", x=str(e["lx"]+e["lw"]/2-midpoint[0]),
                      y=str(e["ly"]+e["lh"]/2-midpoint[1]), **{"as": "offset"})
    if d.id != "level-0":
        footer_y = d.h-155
        text_cell(root, "rules-title", "PROCESS RULES / SCOPE", 40, footer_y, d.w-80, 24, 16, BLUE, True, align="left")
        text_cell(root, "rules-body", "\n".join(d.notes), 40, footer_y+32, d.w-80, 111, 16, MUTED, align="left")
    return page


def render(d):
    """Render the same graph as SVG plus a crisp, high-resolution PNG."""
    scale = 1.5
    image = Image.new("RGB", (round(d.w*scale), round(d.h*scale)), "white")
    draw = ImageDraw.Draw(image)
    svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{d.w}" height="{d.h}" viewBox="0 0 {d.w} {d.h}">',
           '<defs><marker id="arrow" markerWidth="12" markerHeight="12" refX="10" refY="5" orient="auto" markerUnits="userSpaceOnUse"><path d="M0,0 L10,5 L0,10 L2,5 Z" fill="'+LINE+'"/></marker></defs>',
           f'<rect width="{d.w}" height="{d.h}" fill="white"/>']

    def rect(x,y,w,h,fill,stroke=None,radius=0):
        box = [round(x*scale),round(y*scale),round((x+w)*scale),round((y+h)*scale)]
        draw.rounded_rectangle(box, radius=round(radius*scale), fill=fill, outline=stroke, width=3) if radius else draw.rectangle(box, fill=fill, outline=stroke, width=3)
        svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{radius}" fill="{fill}" stroke="{stroke or "none"}" stroke-width="2"/>')

    def txt(x,y,w,h,value,size=17,color=INK,bold=False,align="center",background=None):
        lines = value.splitlines()
        f = font(size*scale,bold)
        lineh = (size+5)
        if background:
            rect(x,y,w,h,background)
        top = y+(h-lenh if (lenh := len(lines)*lineh) else h)/2
        for i,line in enumerate(lines):
            tw = f.getlength(line)/scale
            xx = x+(w-tw)/2 if align == "center" else x
            yy = top+i*lineh
            draw.text((round(xx*scale),round(yy*scale)),line,font=f,fill=color)
            svg.append(f'<text x="{xx}" y="{yy+size}" fill="{color}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}">{html.escape(line)}</text>')

    txt(40,30,d.w-80,45,d.title,32,bold=True,align="left")
    txt(40,83,d.w-80,33,d.subtitle,18,MUTED,align="left")
    txt(40,124,d.w-80,30,"RECTANGLE = external entity   |   ROUNDED BOX = process   |   Dn OPEN STORE = persistent data   |   ARROW = named data flow   |   repeated E/D = same entity/store",16,MUTED,align="left")
    for e in d.edges:
        pts = e["points"]
        draw.line([(round(x*scale),round(y*scale)) for x,y in pts],fill=LINE,width=3)
        a,b=pts[-2:]
        angle=math.atan2(b[1]-a[1],b[0]-a[0]); length=13; width=5
        rear=(b[0]-length*math.cos(angle),b[1]-length*math.sin(angle))
        arrow=[b,(rear[0]+width*math.sin(angle),rear[1]-width*math.cos(angle)),(rear[0]-width*math.sin(angle),rear[1]+width*math.cos(angle))]
        draw.polygon([(round(x*scale),round(y*scale)) for x,y in arrow],fill=LINE)
        path=" ".join(("M" if i==0 else "L")+f"{x},{y}" for i,(x,y) in enumerate(pts))
        svg.append(f'<path d="{path}" fill="none" stroke="{LINE}" stroke-width="2" marker-end="url(#arrow)"/>')
    for n in d.nodes.values():
        x,y,w,h,kind=n["x"],n["y"],n["w"],n["h"],n["kind"]
        if kind=="band":
            txt(x,y,w,25,n["title"],16,BLUE,True,"left")
        elif kind=="process":
            rect(x,y,w,h,"#f0f6fd",BLUE,14)
            txt(x+15,y+12,w-30,32,n["ref"],23,BLUE,True)
            txt(x+12,y+50,w-24,45,n["title"],22,INK,True)
            txt(x+14,y+105,w-28,h-120,n["body"],17,MUTED)
        elif kind=="external":
            rect(x,y,w,h,"#f7f1fc",PURPLE)
            txt(x+8,y+10,w-16,33,n["title"],21,PURPLE,True)
            txt(x+10,y+49,w-20,h-57,n["body"],16,MUTED)
        elif kind=="store":
            rect(x,y,w,h,"#eff8f5")
            for ax,ay,bx,by in [(x,y,x+w,y),(x,y+h,x+w,y+h),(x,y,x,y+h),(x+65,y,x+65,y+h)]:
                draw.line([(round(ax*scale),round(ay*scale)),(round(bx*scale),round(by*scale))],fill=GREEN,width=3)
                svg.append(f'<path d="M{ax},{ay} L{bx},{by}" stroke="{GREEN}" stroke-width="2"/>')
            txt(x+6,y,52,h,n["ref"],24,GREEN,True)
            txt(x+78,y+7,w-91,34,n["title"],20,INK,True,"left")
            txt(x+78,y+49,w-91,h-55,n["body"],15,MUTED,align="left")
    for e in d.edges:
        txt(e["lx"],e["ly"],e["lw"],e["lh"],e["label"],e["size"],LINE,background="white")
    if d.id!="level-0":
        fy=d.h-155
        txt(40,fy,d.w-80,24,"PROCESS RULES / SCOPE",16,BLUE,True,"left")
        txt(40,fy+32,d.w-80,111,"\n".join(d.notes),16,MUTED,align="left")
    svg.append("</svg>")
    prefix={"level-0":"01-level-0", "inventory-detail":"02-inventory-detail", "pos-detail":"03-pos-detail", "backup-detail":"04-backup-detail"}[d.id]
    image.save(OUT/(prefix+".png"))
    (OUT/(prefix+".svg")).write_text("\n".join(svg),encoding="utf-8")


def validate(diagrams):
    """Check DFD semantics, bounds, text fitting and editable XML references."""
    problems=[]
    for d in diagrams:
        for e in d.edges:
            s,t=d.nodes[e["source"]],d.nodes[e["target"]]
            if "process" not in (s["kind"],t["kind"]):
                problems.append(f'{d.id}: illegal {s["kind"]}-to-{t["kind"]} flow')
            if not e["label"]: problems.append(f'{d.id}: unlabeled flow')
            if measure(e["label"],e["size"]) > e["lw"]-4:
                problems.append(f'{d.id}: flow text too wide: {e["label"]!r}')
        for n in d.nodes.values():
            if n["kind"]=="process":
                if not any(e["target"]==n["id"] for e in d.edges): problems.append(f'{d.id}: process {n["id"]} has no input')
                if not any(e["source"]==n["id"] for e in d.edges): problems.append(f'{d.id}: process {n["id"]} has no output')
                if measure(n["title"],22,True)>n["w"]-24: problems.append(f'{d.id}: title too wide: {n["title"]}')
                if measure(n["body"],17)>n["w"]-28: problems.append(f'{d.id}: body too wide: {n["title"]}')
            if n["kind"]=="store" and measure(n["body"],15)>n["w"]-91:
                problems.append(f'{d.id}: store body too wide: {n["ref"]}')
            if n["x"]<0 or n["y"]<0 or n["x"]+n["w"]>d.w or n["y"]+n["h"]>d.h:
                problems.append(f'{d.id}: node outside canvas: {n["id"]}')
    if problems:
        raise ValueError("\n".join(problems))


def guide(diagrams):
    lines=["# Updated Data Flow Diagrams", "", "Updated: 10 October 2026. Native editable draw.io shapes, connectors and text; no embedded raster diagrams.",
           "", "## Pages", "", "1. Level 0: the five main processes and their actual data stores.",
           "2. Level 1 inventory: validation, catalog/photos, receiving, batch corrections and stock filtering.",
           "3. Level 1 POS: cart, validation, FIFO allocation, atomic sale/stock commit and receipts.",
           "4. Level 1 backup: configuration, scheduler, local snapshot, retention/cloud copy and restore.",
           "", "The supplied figure's convention is retained: Level 0 is the system decomposition with 1.0-5.0 processes. A single-process context diagram is not included.",
           "", "## Corrections to the supplied diagram", "", "- Labor, service-status flows and service-sale records were removed: the current routes/controllers implement product sales.",
           "- External entities are people or supporting services. Data stores connect only to processes. Arrows name data rather than physical products, cash or services.",
           "- Customer order/tender data is entered by an authenticated POS operator. There is no customer login/master table or online payment-gateway verification.",
           "- Received inventory and sellable stock are tracked per batch. Price, expiration and condition belong to stock batches, not product master rows.",
           "- Existing tables are grouped into logical DFD stores; repeated store/entity symbols reduce connector crossings and refer to the same store/entity.",
           "- Dashboard/reporting includes date-filtered sales, current stock indicators and sales rankings; admin access is distinct from own-transaction access.",
           "- Account recovery, configured backup times, local files, optional cloud upload and validated restores are included.",
           "", "## Logical store dictionary", "", "| Store | Meaning | Physical records/files |", "|---|---|---|"]
    for ref,(title,body) in STORES.items(): lines.append(f'| {ref} | {title} | {body.replace(chr(10),"; ")} |')
    lines += ["", "D7 is the existing SQL database containing the SQL parts of D1-D4 and framework tables. It is shown as an aggregate for backup/restore; it does not include D1's image files or D5's settings file.",
              "", "## Process rules and scope", ""]
    for d in diagrams:
        lines.append(f'### {d.name}')
        lines.append("")
        lines.extend("- "+note for note in d.notes)
        lines.append("")
    lines += ["## Evidence", "", "The backup table/column names were cross-checked with output/erd/schema-metadata.json. Behavior was checked in:", "", 
              "- routes/web.php and routes/auth.php", "- app/Http/Controllers/StockInController.php", "- app/Http/Controllers/ProductController.php and CategoryController.php",
              "- app/Http/Controllers/PosController.php", "- app/Models/Product.php", "- app/Http/Controllers/DashboardController.php, ReportController.php and TransactionController.php",
              "- app/Http/Controllers/SecurityController.php and Auth password-recovery controllers", "- app/Http/Controllers/BackupController.php",
              "- app/Services/BackupSchedule.php and SqlServerSnapshotService.php", "- app/Services/PrintableReport.php and routes/console.php",
              "", "data-flows.csv gives the precise source, destination and payload description of every displayed arrow. These are documentation files; no application or database records were changed.", ""]
    (OUT/"README.md").write_text("\n".join(lines),encoding="utf-8")


def main():
    OUT.mkdir(parents=True,exist_ok=True)
    diagrams=[main_level_zero(),inventory_level_one(),pos_level_one(),backup_level_one()]
    validate(diagrams)
    mxfile=ET.Element("mxfile", host="app.diagrams.net", type="device", pages=str(len(diagrams)), compressed="false")
    for d in diagrams:
        xml_page(mxfile,d)
        render(d)
    xml=ET.tostring(mxfile,encoding="unicode",xml_declaration=True)
    target=OUT/"updated-system-dfd-2026-10-10.drawio"
    target.write_text(xml,encoding="utf-8")
    for page in mxfile:
        cells=page.findall(".//mxCell")
        ids=[c.get("id") for c in cells]
        assert len(ids)==len(set(ids)), "Duplicate draw.io cell IDs"
        for c in cells:
            for attr in ("source","target","parent"):
                assert c.get(attr) is None or c.get(attr) in ids, f"Broken {attr} reference"
    with (OUT/"data-flows.csv").open("w",newline="",encoding="utf-8-sig") as stream:
        writer=csv.writer(stream)
        writer.writerow(["page","flow_id","source_process_entity_or_store","target_process_entity_or_store","data_payload"])
        for d in diagrams:
            for e in d.edges:
                s,t=d.nodes[e["source"]],d.nodes[e["target"]]
                writer.writerow([d.name,e["id"],f'{s["ref"]} {s["title"]}',f'{t["ref"]} {t["title"]}',e["label"].replace("\n"," ")])
    guide(diagrams)
    encoded=urllib.parse.quote(xml,safe="~()*!.'-")
    compressor=zlib.compressobj(9,zlib.DEFLATED,-15)
    blob=base64.b64encode(compressor.compress(encoded.encode())+compressor.flush()).decode()
    launch="https://app.diagrams.net/?splash=0&grid=0#create="+urllib.parse.quote(json.dumps(dict(type="xml",compressed=True,data=blob),separators=(",",":")),safe="")
    tmp=ROOT/"tmp"/"dfd"
    tmp.mkdir(parents=True,exist_ok=True)
    (tmp/"drawio-open-url.txt").write_text(launch,encoding="utf-8")
    print(json.dumps(dict(file=str(target),pages=len(diagrams),flows=sum(len(d.edges) for d in diagrams),
                          processes=sum(n["kind"]=="process" for d in diagrams for n in d.nodes.values()),
                          native_cells=len(mxfile.findall(".//mxCell"))),indent=2))


if __name__=="__main__":
    main()
