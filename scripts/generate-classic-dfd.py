"""Rebuild the supplied DFD as editable, monochrome draw.io diagrams.

This documents the reference's intended product-and-service workflow. Labor and
service records are retained from the reference, not asserted to exist in code.
No application or database data is changed.
"""
from __future__ import annotations

import base64
import csv
import html
import importlib.util
import json
import math
from pathlib import Path
import urllib.parse
import xml.etree.ElementTree as ET
import zlib

from PIL import Image, ImageDraw, ImageFont

_spec = importlib.util.spec_from_file_location("dfd_support", Path(__file__).with_name("generate-system-dfd.py"))
_support = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(_support)
Diagram = _support.Diagram

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "dfd"
BLACK = "#000000"
WHITE = "#ffffff"
FONTS = Path("C:/Windows/Fonts")


def font(size, bold=False, serif=False):
    name = ("timesbd.ttf" if bold else "times.ttf") if serif else ("arialbd.ttf" if bold else "arial.ttf")
    return ImageFont.truetype(str(FONTS / name), round(size))


def process(d, ident, x, y, title, body, w=270, h=205):
    return d.node(ident, "process", x, y, w, h, title, body, ident)


def external(d, ident, x, y, title, w=170, h=85):
    return d.node(ident, "external", x, y, w, h, title, "", ident)


def store(d, ident, ref, x, y, title, body, w=330, h=100):
    return d.node(ident, "store", x, y, w, h, title, body, ref)


def flow(d, s, t, label, side1, side2, sf, tf, via, at, w=230, size=16):
    if via == []:
        a,b = d.point(s,side1,sf),d.point(t,side2,tf)
        if a[0] != b[0] and a[1] != b[1]:
            if side1 in ("left","right"):
                mid=(a[0]+b[0])/2;via=[(mid,a[1]),(mid,b[1])]
            else:
                mid=(a[1]+b[1])/2;via=[(a[0],mid),(b[0],mid)]
    d.edge(s, t, label, side1, side2, sf, tf, via, at, w, size)


def overview():
    d = Diagram("level-0", "Level 0 - Reference layout", "Figure 3. Level 0 Data Flow Diagram", "", 1800, 1190, [])
    process(d, "3.0", 180, 160, "Reports", "Select dates / report type\nSummarize sales and services\nReview low / out-of-stock items\nPrint or download PDF", 250, 180)
    external(d, "owner", 1020, 190, "Owner")
    process(d, "1.0", 730, 420, "Inventory", "Add / edit / archive products\nManage categories and photos\nReceive shipment / record batch\nSet prices, expiry and condition\nFilter, sort and monitor stock")
    process(d, "2.0", 1050, 695, "Sales", "Validate order and availability\nCalculate total / payment / change\nAllocate stock using FIFO\nRecord product and service sales\nTrack service completion\nIssue receipt", 270, 220)
    external(d, "labor", 1570, 740, "Labor")
    external(d, "customer", 1090, 1020, "Customer", 210, 90)
    store(d, "products", "D1", 260, 460, "Records Product", "Catalog, categories and stock batches\nPrices, expiry, condition and photos", 320)
    store(d, "product-sales", "D2", 620, 790, "Records Product Sale", "Sale ID, items, quantities and totals\nPayment method, tender and change", 300, 110)
    store(d, "service-sales", "D3", 385, 1000, "Records Service Sale", "Service description, charge and status\nLinked sale / receipt and completion", 335, 108)

    flow(d, "3.0", "owner", "Overall report\n(sales, services and inventory)", "top", "top", .5, .5,
         [(305, 120), (1105, 120)], (545, 77), 325)
    flow(d, "owner", "3.0", "Report type, date range\nand print / PDF choice", "left", "right", .25, .25,
         [(960, 211.25), (960, 158), (480, 158), (480, 205)], (620, 165), 270)
    flow(d, "owner", "1.0", "Product / category changes,\nimages and shipment details", "left", "top", .7, .5,
         [(865, 249.5)], (640, 290), 250)
    flow(d, "owner", "2.0", "Sales transaction details\n(items, quantities, prices,\nservice request / approval)", "bottom", "top", .5, .2,
         [(1105, 300), (1104, 300)], (1130, 445), 290)
    flow(d, "1.0", "products", "Saved product,\ncategory and\nshipment details", "left", "right", .3, .2,
         [(655, 481.5), (655, 480)], (586, 413), 140, 14)
    flow(d, "products", "1.0", "Catalog / batch\ndata for updates", "right", "left", .75, .75,
         [(670, 535), (670, 573.75)], (584, 580), 143, 14)
    flow(d, "products", "3.0", "Product / inventory data\n(prices, stock, expiry)", "top", "bottom", .25, .65,
         [(340, 370), (342.5, 370)], (110, 366), 220, 14)
    flow(d, "1.0", "2.0", "Sellable product details\nand available quantities", "bottom", "left", .3, .2,
         [(811, 675), (1015, 675), (1015, 739)], (790, 631), 240)
    flow(d, "2.0", "products", "Allocated batch IDs /\nremaining stock updates", "bottom", "right", .15, .8,
         [(1090.5, 930), (600, 930), (600, 540)], (320, 649), 265)
    flow(d, "2.0", "product-sales", "Record purchase details\n(items, qty, total, payment, cashier)", "left", "top", .3, .85,
         [(875, 761), (875, 790)], (650, 711), 340)
    flow(d, "product-sales", "3.0", "Sales data\n(revenue, quantities sold, payment totals)", "left", "left", .5, .5,
         [(90, 845), (90, 250)], (150, 790), 370)
    flow(d, "2.0", "service-sales", "Record service details\n(description, charge, job status)", "bottom", "top", .4, .5,
         [(1158, 970), (552.5, 970)], (730, 976), 315)
    flow(d, "service-sales", "3.0", "Services data\n(charges, completed / pending jobs)", "left", "left", .5, .22,
         [(55, 1054), (55, 199.6)], (105, 1000), 275, 14)
    flow(d, "customer", "2.0", "Order and payment details\n(Cash / GCash, tender, reference)", "left", "left", .5, .78,
         [(1000, 1065), (1000, 866.6)], (750, 1080), 330, 14)
    flow(d, "2.0", "customer", "Product / service details\nand receipt\n(total, payment and change)", "bottom", "top", .7, 149/210,
         [(1239, 1010)], (1325, 955), 290)
    flow(d, "2.0", "labor", "Service job details\n(description, items and instructions)", "right", "left", .72, .75,
         [(1500, 853.4), (1500, 803.75)], (1345, 866), 370)
    flow(d, "labor", "2.0", "Service status\n(accepted, in progress, completed)\nand completion details", "left", "right", .2, .25,
         [(1480, 757), (1480, 750)], (1360, 638), 365)
    return d


def inventory_detail():
    d = Diagram("inventory-detail", "Level 1 - Inventory", "Figure 3a. Inventory — Detailed Processes", "", 2100, 1190, [])
    external(d, "owner", 70, 220, "Owner", 180)
    process(d, "1.1", 400, 200, "Maintain Catalog", "Validate product / category\nAdd, edit or archive entries\nUpload / replace product photos\nSave names and image paths", 300)
    process(d, "1.2", 850, 200, "Receive Stock", "Select new / existing product\nValidate quantity and prices\nSet expiry and batch condition\nRecord receiving user and date", 300)
    process(d, "1.3", 1300, 200, "Correct Batch Data", "Load batch and quantity sold\nValidate corrected quantity\nEdit price, expiry and condition\nPreserve already-sold quantities", 310)
    process(d, "1.4", 1320, 710, "Monitor / Filter Stock", "Read remaining batch balances\nExclude damaged / expired stock\nFlag low / out-of-stock items\nApply search, category and order", 310)
    external(d, "owner-view", 1770, 760, "Owner", 230)
    store(d, "catalog", "D1", 420, 535, "Records Product — Catalog", "Product / category / archive status\nDescription, photos and image paths", 430)
    store(d, "batches", "D1", 870, 535, "Records Product — Batches", "Qty received / remaining, cost and retail\nExpiration, condition, receiving user / date", 470)
    external(d, "sales", 730, 930, "2.0 Sales", 260)

    flow(d, "owner", "1.1", "Product / category changes\nand image files", "right", "left", .5, .3, [], (255, 140), 230)
    flow(d, "1.1", "1.2", "Selected product details\nand category", "right", "left", .3, .3, [], (703, 130), 200, 14)
    flow(d, "owner", "1.2", "Shipment details\n(quantity, cost, retail, expiry, condition)", "top", "top", .5, .5,
         [(160, 98), (1000, 98)], (350, 55), 470)
    flow(d, "owner", "1.3", "Batch correction request\nand replacement field values", "bottom", "top", .5, .6,
         [(160, 448), (1240, 448), (1240, 115), (1486, 115)], (755, 408), 370)
    flow(d, "1.1", "catalog", "Saved product, category,\narchive status and image paths", "bottom", "top", .3, .25,
         [(490, 475), (527.5, 475)], (400, 465), 340)
    flow(d, "catalog", "1.1", "Existing catalog /\ncategory data", "top", "bottom", .85, .8,
         [(785.5, 500), (640, 500)], (745, 470), 115, 14)
    flow(d, "1.2", "batches", "New stock-in batch\nand initial remaining quantity", "bottom", "top", .3, .3,
         [(940, 482), (1011, 482)], (865, 467), 370)
    flow(d, "batches", "1.3", "Existing batch values\nand quantity already sold", "right", "bottom", .5, .8,
         [(1660, 585), (1660, 475), (1548, 475)], (1610, 495), 355)
    flow(d, "1.3", "batches", "Validated batch corrections\nand recalculated remaining quantity", "bottom", "top", .25, .9,
         [(1377.5, 470), (1293, 470)], (1370, 410), 430)
    flow(d, "catalog", "1.4", "Catalog labels, categories\nand active / archived status", "bottom", "left", .3, .35,
         [(549, 781.75)], (620, 700), 390)
    flow(d, "batches", "1.4", "Stock balances, expiry,\ncondition and selling prices", "bottom", "top", .8, .5,
         [(1246, 680), (1475, 680)], (1040, 640), 380)
    flow(d, "owner-view", "1.4", "Search, category, stock status\nand sorting option", "top", "right", .5, .2,
         [(1885, 670), (1700, 670), (1700, 751)], (1695, 620), 350)
    flow(d, "1.4", "owner-view", "Filtered inventory / receiving log\nLow / out-of-stock alerts", "right", "left", .8, .75,
         [(1690, 874), (1690, 823.75)], (1660, 885), 395)
    flow(d, "1.4", "sales", "Sellable products, available quantities\nand current retail prices", "bottom", "right", .4, .5,
         [(1444, 1025), (1050, 1025), (1050, 972.5)], (1100, 965), 460)
    return d


def sales_detail():
    d = Diagram("pos-detail", "Level 1 - Sales and services", "Figure 3b. Sales — Detailed Processes", "", 2340, 1540, [])
    external(d, "owner", 60, 210, "Owner / POS Staff", 240)
    external(d, "customer", 60, 470, "Customer", 240)
    process(d, "2.1", 490, 210, "Validate Order", "Check selected items / quantities\nCheck active and sellable products\nRead current prices / stock\nReturn errors or validated order", 330)
    process(d, "2.2", 1040, 210, "Calculate Payment", "Calculate line totals / total due\nChoose Cash / GCash\nValidate tender and reference\nCalculate change / paid status", 330)
    process(d, "2.3", 1600, 210, "Record Product Sale", "Lock eligible stock batches\nAllocate oldest batches first (FIFO)\nSave sale header and sold items\nDeduct allocated quantities", 350)
    process(d, "2.4", 1050, 930, "Manage Service Job", "Record request / quoted charge\nSend job details to Labor\nReceive progress / completion\nSave service charge and status", 330)
    process(d, "2.5", 1610, 1030, "Issue Receipt", "Load sale and line-item records\nInclude service charges if present\nShow tender, total and change\nReturn receipt / transaction result", 350)
    external(d, "labor", 500, 930, "Labor", 220, 95)
    external(d, "customer-out", 2060, 1080, "Customer", 220)
    store(d, "stock", "D1", 495, 650, "Records Product", "Active products / category / retail prices\nEligible batches and remaining quantities", 440)
    store(d, "product-sales", "D2", 1600, 650, "Records Product Sale", "Sale / cashier IDs, items, qty, totals\nPayment, tender, change, GCash reference", 510)
    store(d, "service-sales", "D3", 1010, 1270, "Records Service Sale", "Sale link, service description / charge\nAssigned job status and completion details", 480)

    flow(d, "owner", "2.1", "Selected products / quantities\nand authorized price details", "right", "left", .5, .25,
         [(390, 252.5), (390, 261.25)], (305, 140), 280)
    flow(d, "customer", "2.1", "Customer order\nand service requirements", "right", "left", .5, .8,
         [(400, 512.5), (400, 374)], (310, 421), 270)
    flow(d, "2.1", "2.2", "Validated order\n(items, qty, prices, service charge)", "right", "left", .3, .3, [], (828, 135), 315, 15)
    flow(d, "customer", "2.2", "Payment details\n(method, amount tendered, GCash reference)", "bottom", "bottom", .5, .2,
         [(180, 585), (1106, 585)], (475, 528), 600)
    flow(d, "2.2", "2.3", "Accepted payment /\nconfirmed product sale", "right", "left", .4, .4, [], (1390, 175), 190)
    flow(d, "stock", "2.1", "Product availability,\nbatch eligibility and retail prices", "top", "bottom", .3, .3,
         [(627, 495), (589, 495)], (480, 475), 410)
    flow(d, "stock", "2.3", "Eligible stock batches\n(ID, quantity, cost, expiry, condition)", "right", "bottom", .5, .2,
         [(980, 700), (980, 485), (1670, 485)], (1080, 437), 475)
    flow(d, "2.3", "stock", "Allocated batch IDs\nand reduced remaining quantities", "bottom", "right", .05, .8,
         [(1617.5, 565), (980, 565), (980, 730)], (1170, 594), 405)
    flow(d, "2.3", "product-sales", "Sale header, sold items,\ntotals and payment details", "bottom", "top", .7, .7, [], (1840, 470), 360)
    flow(d, "2.1", "2.4", "Validated service request\n(description, items, requested work)", "bottom", "top", .9, .15,
         [(787, 625), (455, 625), (455, 840), (1099.5, 840)], (785, 770), 405)
    flow(d, "2.2", "2.4", "Service charge / payment status", "bottom", "top", .8, .8,
         [(1304, 870), (1314, 870)], (1330, 790), 350)
    flow(d, "2.4", "labor", "Service job details\nand work instructions", "left", "right", .3, .3, [], (740, 910), 270)
    flow(d, "labor", "2.4", "Service status\nand completion details", "bottom", "left", .5, .8,
         [(610, 1140), (970, 1140), (970, 1094)], (680, 1145), 285)
    flow(d, "2.4", "service-sales", "Saved service description,\ncharge, sale link and job status", "bottom", "top", .6, .5,
         [(1248, 1200), (1250, 1200)], (1005, 1178), 480)
    flow(d, "product-sales", "2.5", "Saved sale, sold items\nand payment / change details", "bottom", "top", .7, .7,
         [(1957, 850), (1855, 850)], (1750, 805), 430)
    flow(d, "service-sales", "2.5", "Service charge, status\nand linked transaction details", "right", "bottom", .5, .3,
         [(1520, 1320), (1715, 1320)], (1540, 1360), 385)
    flow(d, "2.5", "customer-out", "Receipt and product /\nservice transaction details", "right", "left", .3, .5,
         [(2000, 1091.5), (2000, 1122.5)], (1980, 1195), 335)
    flow(d, "2.5", "owner", "Transaction confirmation /\nreceipt and recorded sale ID", "top", "top", .3, .5,
         [(1715, 970), (2225, 970), (2225, 95), (180, 95)], (1110, 75), 470)
    return d


def reports_detail():
    d = Diagram("reports-detail", "Level 1 - Reports", "Figure 3c. Reports — Detailed Processes", "", 2000, 1050, [])
    external(d, "owner", 75, 210, "Owner", 200)
    process(d, "3.1", 460, 200, "Select Report Criteria", "Select date / reporting period\nChoose sales, service or inventory\nApply product / category filters\nChoose print or PDF output", 335)
    process(d, "3.2", 1050, 200, "Summarize Records", "Sum revenue and quantities sold\nRank top / least-selling products\nCalculate low / out-of-stock items\nSummarize service charges / status", 380)
    process(d, "3.3", 1110, 705, "Generate Report", "Build totals, lists and summaries\nPrepare readable report pages\nPrint or download PDF\nReturn selected report to Owner", 335)
    external(d, "owner-out", 1630, 750, "Owner", 210)
    store(d, "products", "D1", 65, 550, "Records Product", "Product / category / batch balances\nPrices, expiry and stock condition", 390)
    store(d, "product-sales", "D2", 590, 550, "Records Product Sale", "Dates, sold items, qty, totals\nCashier and payment details", 385)
    store(d, "service-sales", "D3", 1480, 550, "Records Service Sale", "Service charges, dates and job status\nCompletion and linked transaction", 440)

    flow(d, "owner", "3.1", "Report type, dates, filters\nand output preference", "right", "left", .5, .3, [], (285, 140), 295)
    flow(d, "3.1", "3.2", "Validated report criteria\nand selected date range", "right", "left", .3, .3, [], (810, 160), 235)
    flow(d, "products", "3.2", "Product / inventory data\n(stock, price, category and expiry)", "top", "bottom", .5, .1,
         [(260, 473), (1088, 473)], (350, 418), 335, 14)
    flow(d, "product-sales", "3.2", "Sales data within selected dates\n(items, quantity, revenue and cashier)", "top", "bottom", .5, .4,
         [(782.5, 505), (1202, 505)], (690, 485), 300, 14)
    flow(d, "service-sales", "3.2", "Service data within selected dates\n(charges, pending / completed jobs)", "top", "right", .5, .8,
         [(1700, 475), (1470, 475), (1470, 364)], (1480, 395), 465)
    flow(d, "3.2", "3.3", "Aggregated totals / stock alerts\nRankings and service summaries", "bottom", "top", .8, .5,
         [(1354, 685), (1277.5, 685)], (1010, 485), 430)
    flow(d, "3.3", "owner-out", "Overall report\n(printable / PDF output)", "right", "left", .5, .5,
         [(1520, 807.5), (1520, 792.5)], (1455, 855), 325)
    return d


def style(**values):
    return "".join(f"{k}={v};" for k, v in values.items())


def cell(root, ident, value, styling, x, y, w, h, parent="1", **attrs):
    c = ET.SubElement(root, "mxCell", id=ident, value=value, style=styling, vertex="1", parent=parent, **attrs)
    ET.SubElement(c, "mxGeometry", x=str(x), y=str(y), width=str(w), height=str(h), **{"as": "geometry"})
    return c


def textcell(root, ident, value, x, y, w, h, size, bold=False, parent="1", bg="none", serif=False):
    return cell(root, ident, value, style(shape="text", html=0, whiteSpace="wrap", overflow="hidden", fillColor=bg,
                strokeColor="none", fontColor=BLACK, fontFamily="Times New Roman" if serif else "Arial",
                fontSize=size, fontStyle=int(bold), align="center", verticalAlign="middle", spacing=0),
                x, y, w, h, parent, connectable="0")


def port(side, fraction):
    return {"left": (0, fraction), "right": (1, fraction), "top": (fraction, 0), "bottom": (fraction, 1)}[side]


def textparts(n):
    w, h = n["w"], n["h"]
    if n["kind"] == "process":
        return [(n["ref"], 8, 1, w-16, 27, 18, False),
                (n["title"], 9, 35, w-18, 34, 20, False),
                (n["body"], 10, 77, w-20, h-85, 15, False)]
    if n["kind"] == "external":
        return [(n["title"], 8, 0, w-16, h, 20, False)]
    return [(n["ref"], 2, 0, 49, h, 18, False),
            (n["title"], 59, 8, w-68, 28, 18, False),
            (n["body"], 59, 40, w-68, h-46, 13, False)]


def graphxml(mxfile, d):
    page = ET.SubElement(mxfile, "diagram", id=d.id, name=d.name)
    model = ET.SubElement(page, "mxGraphModel", dx="1800", dy="1190", grid="0", gridSize="10", guides="1",
                          tooltips="1", connect="1", arrows="1", fold="1", page="1", pageScale="1",
                          pageWidth=str(d.w), pageHeight=str(d.h), background=WHITE, adaptiveColors="none")
    root = ET.SubElement(model, "root")
    ET.SubElement(root, "mxCell", id="0")
    ET.SubElement(root, "mxCell", id="1", parent="0")
    textcell(root, "heading", d.title, 40, 15, d.w-80, 40, 28, True, serif=True)
    for e in d.edges:
        ex, ey = port(e["source_side"], e["sf"])
        ix, iy = port(e["target_side"], e["tf"])
        edge = ET.SubElement(root, "mxCell", id=e["id"], value="", edge="1", parent="1", source=e["source"], target=e["target"],
            style=style(noEdgeStyle=1, rounded=0, html=0, strokeColor=BLACK, strokeWidth=1.5,
                        endArrow="classic", endFill=1, endSize=9, startArrow="none", exitX=ex, exitY=ey, entryX=ix, entryY=iy,
                        exitPerimeter=0, entryPerimeter=0, jumpStyle="arc", jumpSize=8))
        g = ET.SubElement(edge, "mxGeometry", relative="1", **{"as": "geometry"})
        pts = ET.SubElement(g, "Array", **{"as": "points"})
        for x,y in e["points"][1:-1]: ET.SubElement(pts, "mxPoint", x=str(x), y=str(y))
    for n in d.nodes.values():
        ident,w,h = n["id"],n["w"],n["h"]
        cell(root, ident, "", style(shape="rectangle", fillColor="none", strokeColor="none", container=1, recursiveResize=1), n["x"],n["y"],w,h)
        if n["kind"] in ("process", "external"):
            cell(root, ident+"-box", "", style(shape="rectangle", rounded=0, fillColor=WHITE, strokeColor=BLACK, strokeWidth=1.5), 0,0,w,h,ident,connectable="0")
            if n["kind"]=="process":
                cell(root, ident+"-header-line", "", style(shape="rectangle", fillColor=BLACK, strokeColor="none"), 0,28,w,1.5,ident,connectable="0")
        else:
            cell(root, ident+"-paper", "", style(shape="rectangle", fillColor=WHITE, strokeColor="none"), 0,0,w,h,ident,connectable="0")
            for k,x,y,ww,hh in [("top",0,0,w,1.5),("bottom",0,h-1.5,w,1.5),("left",0,0,1.5,h),("partition",53,0,1.5,h)]:
                cell(root, ident+"-"+k, "", style(shape="rectangle", fillColor=BLACK, strokeColor="none"),x,y,ww,hh,ident,connectable="0")
        for i,(v,x,y,ww,hh,size,bold) in enumerate(textparts(n)):
            textcell(root, ident+f"-text-{i}", v,x,y,ww,hh,size,bold,ident)
    for e in d.edges:
        c = textcell(root,e["id"]+"-label",e["label"],0,0,e["lw"],e["lh"],e["size"],parent=e["id"],bg=WHITE)
        g=c.find("mxGeometry");g.set("relative","1")
        total=sum(math.dist(a,b) for a,b in zip(e["points"],e["points"][1:])); walked=0
        for a,b in zip(e["points"],e["points"][1:]):
            seg=math.dist(a,b)
            if walked+seg>=total/2:
                r=(total/2-walked)/seg if seg else 0;mid=(a[0]+r*(b[0]-a[0]),a[1]+r*(b[1]-a[1]));break
            walked+=seg
        # A child vertex on an edge uses its top-left, not its center, as the
        # offset anchor. This must match the separately rendered previews.
        ET.SubElement(g,"mxPoint",x=str(e["lx"]-mid[0]),y=str(e["ly"]-mid[1]),**{"as":"offset"})
    textcell(root,"legend","Rectangle = external entity     |     Numbered box = process     |     D1–D3 = data stores     |     Arrow = data flow",
             40,d.h-47,d.w-80,27,14)


def render(d, basename):
    scale=2
    im=Image.new("RGB",(d.w*scale,d.h*scale),"white");draw=ImageDraw.Draw(im)
    svg=[f'<svg xmlns="http://www.w3.org/2000/svg" width="{d.w}" height="{d.h}" viewBox="0 0 {d.w} {d.h}">',
         '<defs><marker id="arrow" markerWidth="11" markerHeight="10" refX="10" refY="5" orient="auto" markerUnits="userSpaceOnUse"><path d="M0,0 L10,5 L0,10 L2,5 Z" fill="#000000"/></marker></defs>',
         f'<rect width="{d.w}" height="{d.h}" fill="#ffffff"/>']
    def rect(x,y,w,h,stroke=True):
        draw.rectangle((x*scale,y*scale,(x+w)*scale,(y+h)*scale),fill="white",outline="black" if stroke else None,width=3)
        svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="#ffffff" stroke="{"#000000" if stroke else "none"}" stroke-width="1.5"/>')
    def line(a,b):
        draw.line([(x*scale,y*scale) for x,y in (a,b)],fill="black",width=3)
        svg.append(f'<path d="M{a[0]},{a[1]} L{b[0]},{b[1]}" stroke="#000000" stroke-width="1.5" fill="none"/>')
    def text(x,y,w,h,value,size,bold=False,serif=False):
        lines=value.splitlines();f=font(size*scale,bold,serif);lh=size+5;top=y+(h-len(lines)*lh)/2
        for i,v in enumerate(lines):
            xx=x+(w-f.getlength(v)/scale)/2; yy=top+i*lh
            draw.text((xx*scale,yy*scale),v,font=f,fill="black")
            svg.append(f'<text x="{xx}" y="{yy+size}" fill="#000000" font-family="{"Times New Roman, serif" if serif else "Arial, sans-serif"}" font-size="{size}" font-weight="{700 if bold else 400}">{html.escape(v)}</text>')
    text(40,15,d.w-80,40,d.title,28,True,True)
    for e in d.edges:
        pts=e["points"];draw.line([(x*scale,y*scale) for x,y in pts],fill="black",width=3)
        a,b=pts[-2:];theta=math.atan2(b[1]-a[1],b[0]-a[0]);rear=(b[0]-11*math.cos(theta),b[1]-11*math.sin(theta))
        tri=[b,(rear[0]+4.5*math.sin(theta),rear[1]-4.5*math.cos(theta)),(rear[0]-4.5*math.sin(theta),rear[1]+4.5*math.cos(theta))]
        draw.polygon([(x*scale,y*scale) for x,y in tri],fill="black")
        path=" ".join(("M" if i==0 else "L")+f"{x},{y}" for i,(x,y) in enumerate(pts))
        svg.append(f'<path d="{path}" stroke="#000000" stroke-width="1.5" fill="none" marker-end="url(#arrow)"/>')
    for n in d.nodes.values():
        x,y,w,h=n["x"],n["y"],n["w"],n["h"]
        rect(x,y,w,h,n["kind"]!="store")
        if n["kind"]=="process":line((x,y+28),(x+w,y+28))
        if n["kind"]=="store":
            for a,b in [((x,y),(x+w,y)),((x,y+h),(x+w,y+h)),((x,y),(x,y+h)),((x+53,y),(x+53,y+h))]:line(a,b)
        for v,xx,yy,ww,hh,size,bold in textparts(n):text(x+xx,y+yy,ww,hh,v,size,bold)
    for e in d.edges:
        rect(e["lx"],e["ly"],e["lw"],e["lh"],False)
        text(e["lx"],e["ly"],e["lw"],e["lh"],e["label"],e["size"])
    text(40,d.h-47,d.w-80,27,"Rectangle = external entity     |     Numbered box = process     |     D1–D3 = data stores     |     Arrow = data flow",14)
    svg.append("</svg>")
    im.save(OUT/(basename+".png"));(OUT/(basename+".svg")).write_text("\n".join(svg),encoding="utf-8")


def validate(diagrams):
    issues=[]
    for d in diagrams:
        for n in d.nodes.values():
            for v,x,y,w,h,size,bold in textparts(n):
                if max((font(size,bold).getlength(s) for s in v.splitlines()),default=0)>w:
                    issues.append(f"{d.id}: text too wide in {n['id']}: {v}")
                if len(v.splitlines())*(size+5)>h+2:issues.append(f"{d.id}: text too tall in {n['id']}: {v}")
            if n["kind"]=="process":
                assert any(e["target"]==n["id"] for e in d.edges), f"Process {n['id']} has no input"
                assert any(e["source"]==n["id"] for e in d.edges), f"Process {n['id']} has no output"
        for e in d.edges:
            assert "process" in (d.nodes[e["source"]]["kind"],d.nodes[e["target"]]["kind"])
            if max(font(e["size"]).getlength(s) for s in e["label"].splitlines())>e["lw"]:
                issues.append(f"{d.id}: flow text too wide: {e['label']}")
            box=(e["lx"],e["ly"],e["lx"]+e["lw"],e["ly"]+e["lh"])
            for n in d.nodes.values():
                if box[0]<n["x"]+n["w"] and box[2]>n["x"] and box[1]<n["y"]+n["h"] and box[3]>n["y"]:
                    issues.append(f"{d.id}: flow label overlaps {n['id']}: {e['label']}")
        for i,e in enumerate(d.edges):
            for f in d.edges[i+1:]:
                if e["lx"]<f["lx"]+f["lw"] and e["lx"]+e["lw"]>f["lx"] and e["ly"]<f["ly"]+f["lh"] and e["ly"]+e["lh"]>f["ly"]:
                    issues.append(f"{d.id}: overlapping labels: {e['label']} | {f['label']}")
        for e in d.edges:
            for a,b in zip(e["points"],e["points"][1:]):
                if abs(a[0]-b[0])>.01 and abs(a[1]-b[1])>.01:issues.append(f"{d.id}: diagonal flow: {e['label']}")
                for n in d.nodes.values():
                    if n["id"] in (e["source"],e["target"]):continue
                    if abs(a[0]-b[0])<.01 and n["x"]<a[0]<n["x"]+n["w"] and min(a[1],b[1])<n["y"]+n["h"] and max(a[1],b[1])>n["y"]:
                        issues.append(f"{d.id}: flow through {n['id']}: {e['label']}")
                    if abs(a[1]-b[1])<.01 and n["y"]<a[1]<n["y"]+n["h"] and min(a[0],b[0])<n["x"]+n["w"] and max(a[0],b[0])>n["x"]:
                        issues.append(f"{d.id}: flow through {n['id']}: {e['label']}")
    if issues:raise ValueError("\n".join(issues))


def main():
    OUT.mkdir(parents=True,exist_ok=True)
    diagrams=[overview(),inventory_detail(),sales_detail(),reports_detail()]
    validate(diagrams)
    mxfile=ET.Element("mxfile",host="app.diagrams.net",type="device",pages=str(len(diagrams)),compressed="false")
    basenames=["01-level-0","02-inventory-detail","03-pos-detail","04-reports-detail"]
    for d,b in zip(diagrams,basenames):graphxml(mxfile,d);render(d,b)
    xml=ET.tostring(mxfile,encoding="unicode",xml_declaration=True)
    for c in mxfile.findall(".//mxCell"):
        for part in c.get("style","").split(";"):
            if "Color=" in part:assert part.split("=",1)[1] in (BLACK,WHITE,"none")
    for p in mxfile:
        cells=p.findall(".//mxCell");ids=[c.get("id") for c in cells];assert len(ids)==len(set(ids))
        for c in cells:
            for attr in ("parent","source","target"):assert c.get(attr) is None or c.get(attr) in ids
    target=OUT/"updated-system-dfd-2026-10-10.drawio";target.write_text(xml,encoding="utf-8")
    with (OUT/"data-flows.csv").open("w",encoding="utf-8-sig",newline="") as f:
        wr=csv.writer(f);wr.writerow(["page","flow_id","source","destination","data"])
        for d in diagrams:
            for e in d.edges:wr.writerow([d.name,e["id"],e["source"],e["target"],e["label"].replace("\n"," ")])
    readme="""# Black-and-white Data Flow Diagram

The overview follows the supplied Figure 3: Reports at upper left, Owner above Inventory / Sales, Labor at right, Customer below, and D1–D3 at left. All diagram objects are black and white, square-cornered native draw.io shapes with editable text and arrows.

The four pages are Level 0, detailed Inventory (1.1–1.4), detailed Sales and services (2.1–2.5), and detailed Reports (3.1–3.3). Numbered process headers and open-ended stores follow the reference notation. All arrows describe data, not physical money, products or labor.

## Scope

This is the reference's intended business workflow. Labor and service-sale records are retained to honor the supplied photo. The current Laravel application implements product inventory, POS and reports; it does not yet implement Labor assignments or a service-sale table. Service steps on these diagrams are conceptual and are not a claim that those features were added to the application.

D1 is a logical product/inventory store (catalog, categories, statuses, photos and stock batches). D2 groups product-sale headers, line items and payment details. D3 represents the reference's service-sale records. D1 symbols on the detail page are views of the same logical store. Repeated Owner / Customer symbols identify the same external entities. Customer order and payment information is entered by POS staff.

Actual inventory/POS details include photo upload, category/archive management, batch expiration/condition, current remaining quantities, FIFO stock allocation, Cash/GCash details, receipts, automatic inventory filters, date-filtered reports, product rankings and PDF/print output. Existing controller behavior informed these details. No application source or database rows were modified.

`data-flows.csv` lists every displayed arrow. `01-level-0.png` and its SVG are the main diagram; remaining previews correspond to the detailed pages. Open `updated-system-dfd-2026-10-10.drawio` in diagrams.net to edit all elements.
"""
    (OUT/"README.md").write_text(readme,encoding="utf-8")
    encoded=urllib.parse.quote(xml,safe="~()*!.'-");compressor=zlib.compressobj(9,zlib.DEFLATED,-15)
    data=base64.b64encode(compressor.compress(encoded.encode())+compressor.flush()).decode()
    launch="https://app.diagrams.net/?splash=0&grid=0#create="+urllib.parse.quote(json.dumps(dict(type="xml",compressed=True,data=data),separators=(",",":")),safe="")
    tmp=ROOT/"tmp"/"dfd";tmp.mkdir(parents=True,exist_ok=True);(tmp/"drawio-open-url.txt").write_text(launch,encoding="utf-8")
    print(json.dumps(dict(file=str(target),pages=len(diagrams),processes=sum(n["kind"]=="process" for d in diagrams for n in d.nodes.values()),flows=sum(len(d.edges) for d in diagrams),native_cells=len(mxfile.findall(".//mxCell"))),indent=2))


if __name__=="__main__":main()
