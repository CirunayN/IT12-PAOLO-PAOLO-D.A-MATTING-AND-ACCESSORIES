"""Build a simplified, editable monochrome ERD from the saved backup schema.

No records, application files or database state are changed. The full schema
remains in schema-metadata.json and data-dictionary.csv.
"""
from __future__ import annotations

import base64
import argparse
import copy
import html
import json
import math
from pathlib import Path
import urllib.parse
import xml.etree.ElementTree as ET
import zlib

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "erd"
SCHEMA = json.loads((OUT / "schema-metadata.json").read_text(encoding="utf-8"))
BLACK, WHITE = "#000000", "#ffffff"
HEADER, ROW, PAD = 40, 27, 10
WIDTH = 320
FONT_DIR = Path("C:/Windows/Fonts")


def font(size, bold=False):
    return ImageFont.truetype(str(FONT_DIR / ("arialbd.ttf" if bold else "arial.ttf")), round(size))


def style(**values):
    return ";".join(f"{key}={value}" for key, value in values.items()) + ";"


def vertex(root, ident, text, x, y, w, h, parent="1", **styling):
    cell = ET.SubElement(root, "mxCell", id=ident, value=text,
                         style=style(**styling), vertex="1", parent=parent)
    ET.SubElement(cell, "mxGeometry", x=str(x), y=str(y), width=str(w), height=str(h), **{"as": "geometry"})
    return cell


class Page:
    def __init__(self, ident, name, title, filename, width=1580, height=1090):
        self.ident, self.name, self.title, self.filename = ident, name, title, filename
        self.width, self.height = width, height
        self.tables, self.edges, self.labels = {}, [], []

    def table(self, name, x, y, fields=None):
        columns = SCHEMA["tables"][name]["columns"]
        if fields is not None:
            selected = set(fields)
            assert selected <= {c["name"] for c in columns}, (name, selected)
            columns = [c for c in columns if c["name"] in selected]
        self.tables[name] = dict(x=x, y=y, w=WIDTH,
                                 h=HEADER + PAD * 2 + ROW * len(columns), columns=columns)

    def point(self, name, side, column=None, fraction=.5):
        t = self.tables[name]
        if column is not None:
            index = next(i for i, c in enumerate(t["columns"]) if c["name"] == column)
            fraction = (HEADER + PAD + ROW * (index + .5)) / t["h"]
        return {
            "left": (t["x"], t["y"] + t["h"] * fraction),
            "right": (t["x"] + t["w"], t["y"] + t["h"] * fraction),
            "top": (t["x"] + t["w"] * fraction, t["y"]),
            "bottom": (t["x"] + t["w"] * fraction, t["y"] + t["h"]),
        }[side]

    def relation(self, parent, child, child_column, start_side, end_side,
                 start_column=None, end_column=None, via=(), start_fraction=.5, end_fraction=.5):
        relationship = next(r for r in SCHEMA["relationships"]
                            if r["parent"] == parent and r["child"] == child and r["child_column"] == child_column)
        a = self.point(parent, start_side, start_column, start_fraction)
        b = self.point(child, end_side, end_column, end_fraction)
        self.edges.append(dict(r=relationship, points=[a, *via, b]))

    def label(self, text, x, y, w, size=15):
        self.labels.append(dict(text=text, x=x, y=y, w=w, h=24, size=size))


def make_overview():
    page = Page("all-tables", "1 - All 21 tables", "Entity Relationship Diagram - All 21 Database Tables", "00-all-tables-erd", width=3680, height=1980)
    for name, x, y in [
        ("tbl_Category", 100, 160), ("tbl_Status", 620, 160),
        ("tbl_Payment_Method", 1140, 160), ("tbl_Product", 620, 490),
        ("tbl_Stock_in", 100, 960), ("tbl_Sale", 1140, 490),
        ("tbl_Sold_Item", 1140, 1050), ("users", 1700, 490),
        ("admin_recovery_codes", 2200, 160), ("email_change_codes", 2730, 160),
        ("pending_employee_registrations", 3250, 160),
        ("password_reset_codes", 2200, 650), ("password_reset_requests", 2730, 650),
        ("sessions", 3250, 800), ("password_reset_tokens", 2200, 1120),
        ("cache", 100, 1510), ("cache_locks", 620, 1510),
        ("jobs", 1140, 1510), ("job_batches", 1700, 1510),
        ("failed_jobs", 2200, 1510), ("migrations", 2730, 1510),
    ]:
        # The complete overview retains every source column, including timestamps.
        page.table(name, x, y)
    page.relation("tbl_Category", "tbl_Product", "Category_ID", "bottom", "top",
                  end_fraction=.3, via=[(260, 450), (716, 450)])
    page.relation("tbl_Status", "tbl_Product", "Status_ID", "bottom", "top",
                  end_fraction=.7, via=[(780, 400), (844, 400)])
    page.relation("tbl_Payment_Method", "tbl_Sale", "Payment_Method_ID", "bottom", "top")
    page.relation("users", "tbl_Sale", "User_ID", "left", "right", "id", "User_ID",
                  via=[(1600, 553.5), (1600, 580.5)])
    page.relation("users", "tbl_Stock_in", "User_ID", "left", "left", "id", "User_ID",
                  via=[(1580, 553.5), (1580, 930), (65, 930), (65, 1077.5)])
    page.relation("tbl_Product", "tbl_Stock_in", "Product_ID", "bottom", "right",
                  end_column="Product_ID", via=[(780, 1050.5)])
    page.relation("tbl_Product", "tbl_Sold_Item", "Product_ID", "right", "left", "ID", "Product_ID",
                  via=[(1000, 553.5), (1000, 1140.5)])
    page.relation("tbl_Sale", "tbl_Sold_Item", "Sale_ID", "bottom", "top")
    page.relation("users", "admin_recovery_codes", "user_id", "right", "left", "id", "user_id",
                  via=[(2080, 553.5), (2080, 250.5)])
    page.relation("users", "email_change_codes", "user_id", "right", "left", "id", "user_id",
                  via=[(2050, 553.5), (2050, 120), (2630, 120), (2630, 250.5)])
    page.relation("users", "pending_employee_registrations", "created_by", "right", "left", "id", "created_by",
                  via=[(2035, 553.5), (2035, 80), (3210, 80), (3210, 250.5)])
    page.relation("users", "password_reset_requests", "user_id", "right", "left", "id", "user_id",
                  via=[(2650, 553.5), (2650, 740.5)])
    page.relation("users", "password_reset_requests", "approved_by", "right", "left", "id", "approved_by",
                  via=[(2110, 553.5), (2110, 600), (2600, 600), (2600, 767.5)])
    page.relation("users", "sessions", "user_id", "bottom", "left", end_column="user_id", start_fraction=.8,
                  via=[(1956, 1370), (3210, 1370), (3210, 890.5)])
    page.relation("users", "password_reset_codes", "email", "right", "left", "email", "email",
                  via=[(2150, 634.5), (2150, 740.5)])
    page.relation("users", "password_reset_tokens", "email", "right", "left", "email", "email",
                  via=[(2060, 634.5), (2060, 1183.5)])
    page.label("INVENTORY AND SALES", 100, 90, 1350, 19)
    page.label("ACCOUNTS AND RECOVERY", 1700, 90, 1750, 19)
    page.label("SYSTEM TABLES - no declared foreign-key relationships", 100, 1450, 3400, 19)
    page.label("21 unique tables / 144 fields / 13 foreign keys / 3 application references", 100, 1395, 1550, 16)
    page.label("Solid lines = database foreign keys; dashed lines = application references", 1700, 1395, 1750, 16)
    return page


def make_pages():
    business = Page("business", "2 - Inventory and sales", "Entity Relationship Diagram - Inventory and Sales", "01-business-erd")
    business.table("users", 100, 140, ["id", "name", "username", "email", "role", "is_active"])
    business.table("tbl_Category", 620, 140, ["ID", "Name", "Is_Archived"])
    business.table("tbl_Payment_Method", 1140, 140, ["ID", "Name"])
    business.table("tbl_Status", 100, 430, ["ID", "Name"])
    business.table("tbl_Product", 620, 400, ["ID", "Category_ID", "Status_ID", "Name", "Description", "Images", "Image"])
    business.table("tbl_Sale", 1140, 400, ["ID", "User_ID", "Payment_Method_ID", "Date", "Total", "Amount_Received", "Change_Amount", "GCash_Reference_Number"])
    business.table("tbl_Stock_in", 100, 650, [c["name"] for c in SCHEMA["tables"]["tbl_Stock_in"]["columns"] if c["name"] not in ("created_at", "updated_at")])
    business.table("tbl_Sold_Item", 1140, 790, ["ID", "Product_ID", "Sale_ID", "Quantity", "Total"])
    business.relation("tbl_Category", "tbl_Product", "Category_ID", "bottom", "top")
    business.relation("tbl_Status", "tbl_Product", "Status_ID", "right", "left", "ID", "Status_ID", [(490, 493.5), (490, 517.5)])
    business.relation("tbl_Payment_Method", "tbl_Sale", "Payment_Method_ID", "bottom", "top")
    business.relation("users", "tbl_Sale", "User_ID", "right", "right", "id", "User_ID", [(490, 203.5), (490, 100), (1515, 100), (1515, 490.5)])
    business.relation("users", "tbl_Stock_in", "User_ID", "left", "left", "id", "User_ID", [(65, 203.5), (65, 767.5)])
    business.relation("tbl_Product", "tbl_Stock_in", "Product_ID", "bottom", "right", end_column="Product_ID", via=[(780, 740.5)])
    business.relation("tbl_Product", "tbl_Sold_Item", "Product_ID", "right", "left", "ID", "Product_ID", [(1000, 463.5), (1000, 880.5)])
    business.relation("tbl_Sale", "tbl_Sold_Item", "Sale_ID", "bottom", "top")
    business.label("Cashier", 1000, 71, 110)
    business.label("Receiving user (optional)", 95, 580, 250)
    business.label("Stock batches", 580, 711, 160)
    business.label("Sold products", 1008, 706, 130)
    business.label("Sale items", 1310, 713, 100)

    security = Page("security", "3 - Accounts and recovery", "Entity Relationship Diagram - Accounts and Recovery", "02-security-erd", height=1110)
    security.table("users", 620, 420, ["id", "name", "username", "email", "role", "is_active", "email_verified_at", "password", "remember_token"])
    for name, x, y in [
        ("admin_recovery_codes", 100, 140), ("email_change_codes", 620, 140),
        ("pending_employee_registrations", 1140, 140), ("password_reset_codes", 100, 420),
        ("sessions", 100, 740), ("password_reset_requests", 1140, 740),
        ("password_reset_tokens", 620, 870),
    ]:
        security.table(name, x, y, [c["name"] for c in SCHEMA["tables"][name]["columns"] if c["name"] not in ("created_at", "updated_at")])
    security.relation("users", "admin_recovery_codes", "user_id", "left", "right", "id", "user_id", [(540, 483.5), (540, 230.5)])
    security.relation("users", "email_change_codes", "user_id", "top", "bottom")
    security.relation("users", "pending_employee_registrations", "created_by", "right", "left", "id", "created_by", [(1020, 483.5), (1020, 230.5)])
    security.relation("users", "password_reset_requests", "user_id", "right", "left", "id", "user_id", [(1030, 483.5), (1030, 830.5)])
    security.relation("users", "password_reset_requests", "approved_by", "bottom", "left", end_column="approved_by", start_fraction=.8, via=[(876, 755), (1080, 755), (1080, 857.5)])
    security.relation("users", "sessions", "user_id", "left", "right", end_column="user_id", start_fraction=.78, via=[(570, 656.34), (570, 830.5)])
    security.relation("users", "password_reset_codes", "email", "left", "right", "email", "email", [(500, 564.5), (500, 510.5)])
    security.relation("users", "password_reset_tokens", "email", "bottom", "top", start_fraction=.375, end_fraction=.375)
    security.label("Dashed lines = application references; no database foreign key", 95, 1045, 820)

    framework = Page("framework", "4 - System tables", "Entity Relationship Diagram - System Tables", "03-framework-tables", height=1020)
    for name, x, y in [
        ("cache", 100, 150), ("cache_locks", 620, 150), ("jobs", 1140, 150),
        ("migrations", 100, 610), ("job_batches", 620, 610), ("failed_jobs", 1140, 610),
    ]:
        framework.table(name, x, y)
    framework.label("These system tables have no declared foreign-key relationships in this backup schema.", 100, 956, 1230)
    return [make_overview(), business, security, framework]


def normalize_routes(pages):
    # Resolve the row coordinates from schema order, so connectors cannot drift
    # when the selected field count or table padding changes.
    for page in pages:
        for edge in page.edges:
            pts = edge["points"]
            if len(pts) > 2:
                if pts[0][0] != pts[1][0]:
                    pts[1] = (pts[1][0], pts[0][1])
                else:
                    pts[1] = (pts[0][0], pts[1][1])
                if pts[-1][0] != pts[-2][0]:
                    pts[-2] = (pts[-2][0], pts[-1][1])
                else:
                    pts[-2] = (pts[-1][0], pts[-2][1])


def validate(pages):
    shown = set()
    edge_keys = set()
    for page in pages:
        shown.update(page.tables)
        for name, a in page.tables.items():
            for other_name, b in page.tables.items():
                if name >= other_name:
                    continue
                overlap = (max(a["x"], b["x"]) < min(a["x"]+a["w"], b["x"]+b["w"])
                           and max(a["y"], b["y"]) < min(a["y"]+a["h"], b["y"]+b["h"]))
                assert not overlap, (page.name, name, other_name)
        for name, t in page.tables.items():
            assert t["x"] > 0 and t["y"] > 0
            assert t["x"] + t["w"] < page.width and t["y"] + t["h"] < page.height, name
            assert font(18, True).getlength(name) < t["w"] - 14, name
            for c in t["columns"]:
                assert font(17).getlength(c["name"]) < t["w"] - 76, (name, c["name"])
        for edge in page.edges:
            r = edge["r"]
            edge_keys.add((r["parent"], r["child"], r["child_column"]))
            for a, b in zip(edge["points"], edge["points"][1:]):
                assert a[0] == b[0] or a[1] == b[1], (page.name, r, a, b)
                for name, t in page.tables.items():
                    x1, x2, y1, y2 = t["x"], t["x"] + t["w"], t["y"], t["y"] + t["h"]
                    if a[0] == b[0]:
                        overlap = x1 < a[0] < x2 and max(min(a[1], b[1]), y1) < min(max(a[1], b[1]), y2)
                    else:
                        overlap = y1 < a[1] < y2 and max(min(a[0], b[0]), x1) < min(max(a[0], b[0]), x2)
                    assert not overlap, (page.name, r, "line intersects", name)
    assert shown == set(SCHEMA["tables"]), shown ^ set(SCHEMA["tables"])
    expected = {(r["parent"], r["child"], r["child_column"]) for r in SCHEMA["relationships"]}
    assert edge_keys == expected, edge_keys ^ expected
    complete = pages[0]
    assert set(complete.tables) == set(SCHEMA["tables"])
    assert len(complete.edges) == len(SCHEMA["relationships"])
    for name, table in complete.tables.items():
        assert table["columns"] == SCHEMA["tables"][name]["columns"]


def text_style(size=17, bold=False, align="left"):
    return dict(shape="text", html=0, whiteSpace="nowrap", overflow="visible", fillColor="none",
                strokeColor="none", fontColor=BLACK, fontFamily="Arial", fontSize=size,
                fontStyle=int(bold), align=align, verticalAlign="middle", spacing=0)


def native_xml(pages):
    doc = ET.Element("mxfile", host="app.diagrams.net", agent="Codex", version="29.0.0", type="device", pages=str(len(pages)))
    for page in pages:
        d = ET.SubElement(doc, "diagram", id=page.ident, name=page.name)
        model = ET.SubElement(d, "mxGraphModel", dx="1280", dy="720", grid="0", gridSize="10", guides="1", tooltips="1", connect="1", arrows="1", fold="1", page="1", pageScale="1", pageWidth=str(page.width), pageHeight=str(page.height), math="0", shadow="0", background=WHITE, adaptiveColors="none")
        root = ET.SubElement(model, "root")
        ET.SubElement(root, "mxCell", id="0")
        ET.SubElement(root, "mxCell", id="1", parent="0")
        vertex(root, "title", page.title, 100, 24, page.width - 200, 42, **text_style(27, True, "center"))
        for name, t in page.tables.items():
            ident = "table-" + name
            vertex(root, ident, "", t["x"], t["y"], t["w"], t["h"],
                   shape="rectangle", rounded=0, fillColor=WHITE, strokeColor=BLACK, strokeWidth=1.5, shadow=0)
            vertex(root, ident + "-header", name, 0, 0, t["w"], HEADER, parent=ident,
                   shape="rectangle", rounded=0, fillColor=WHITE, strokeColor=BLACK, strokeWidth=1.5,
                   fontColor=BLACK, fontSize=18, fontFamily="Arial", fontStyle=1, align="center", verticalAlign="middle")
            for i, c in enumerate(t["columns"]):
                y = HEADER + PAD + i * ROW
                key = "/".join(c.get("keys", []))
                vertex(root, ident + "-key-" + c["name"], key, 12, y, 49, ROW, parent=ident, **text_style(14, True))
                vertex(root, ident + "-field-" + c["name"], c["name"], 65, y, t["w"] - 76, ROW, parent=ident, **text_style())
        for i, edge in enumerate(page.edges):
            r, pts = edge["r"], edge["points"]
            source, target = page.tables[r["parent"]], page.tables[r["child"]]
            a, b = pts[0], pts[-1]
            cell = ET.SubElement(root, "mxCell", id=f"relation-{i}", value="", edge="1", parent="1",
                                 source="table-" + r["parent"], target="table-" + r["child"],
                                 style=style(noEdgeStyle=1, rounded=0, html=0, strokeColor=BLACK,
                                             strokeWidth=1.5, dashed=int(r["logical"]), dashPattern="6 4",
                                             startArrow="ERzeroToOne" if r["optional_parent"] else "ERmandOne",
                                             endArrow="ERzeroToOne" if r["unique_child"] else "ERzeroToMany",
                                             startFill=0, endFill=0, startSize=16, endSize=16,
                                             exitX=(a[0]-source["x"])/source["w"], exitY=(a[1]-source["y"])/source["h"], exitPerimeter=0,
                                             entryX=(b[0]-target["x"])/target["w"], entryY=(b[1]-target["y"])/target["h"], entryPerimeter=0))
            g = ET.SubElement(cell, "mxGeometry", relative="1", **{"as": "geometry"})
            if len(pts) > 2:
                arr = ET.SubElement(g, "Array", **{"as": "points"})
                for x, y in pts[1:-1]:
                    ET.SubElement(arr, "mxPoint", x=str(x), y=str(y))
        for i, label in enumerate(page.labels):
            vertex(root, "label-" + str(i), label["text"], label["x"], label["y"], label["w"], label["h"], **text_style(label["size"]))
        vertex(root, "legend", "PK = Primary key     FK = Foreign key     UK = Unique key     | = One     O = Optional     Crow's foot = Many", 100, page.height - 39, page.width - 200, 24, **text_style(14))
    ET.indent(doc)
    return ET.tostring(doc, encoding="unicode", xml_declaration=True)


def marker_parts(end, neighbor, kind):
    dx, dy = neighbor[0] - end[0], neighbor[1] - end[1]
    length = math.hypot(dx, dy)
    ux, uy = dx / length, dy / length
    vx, vy = -uy, ux
    def p(d, v=0):
        return end[0] + ux*d + vx*v, end[1] + uy*d + vy*v
    parts = []
    if kind == "many":
        parts.extend(("line", p(0, v), p(14)) for v in (-7, 0, 7))
        parts.append(("circle", p(22), 4))
    elif kind == "optional":
        parts.extend([("line", p(6, -7), p(6, 7)), ("circle", p(18), 4)])
    else:
        parts.extend([("line", p(6, -7), p(6, 7)), ("line", p(12, -7), p(12, 7))])
    return parts


def render(page):
    scale = 2
    im = Image.new("RGB", (page.width*scale, page.height*scale), "white")
    draw = ImageDraw.Draw(im)
    svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{page.width}" height="{page.height}" viewBox="0 0 {page.width} {page.height}">', '<rect width="100%" height="100%" fill="#ffffff"/>']
    def xy(point):
        return tuple(round(v*scale) for v in point)
    def line(a, b, dashed=False):
        if dashed:
            length=math.dist(a,b)
            for offset in range(0, math.ceil(length), 10):
                t1, t2 = offset/length, min(offset+6,length)/length
                aa=tuple(a[j]+(b[j]-a[j])*t1 for j in (0,1))
                bb=tuple(a[j]+(b[j]-a[j])*t2 for j in (0,1))
                draw.line([xy(aa),xy(bb)],fill="black",width=3)
        else:
            draw.line([xy(a),xy(b)],fill="black",width=3)
        dash = ' stroke-dasharray="6 4"' if dashed else ""
        svg.append(f'<line x1="{a[0]}" y1="{a[1]}" x2="{b[0]}" y2="{b[1]}" stroke="#000000" stroke-width="1.5"{dash}/>')
    def box(x,y,w,h):
        draw.rectangle([xy((x,y)),xy((x+w,y+h))],fill="white",outline="black",width=3)
        svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="#ffffff" stroke="#000000" stroke-width="1.5"/>')
    def text(value,x,y,w,h,size=17,bold=False,center=False):
        f = font(size*scale,bold)
        actual = draw.textbbox((0,0),value,font=f)
        x0=(x + w/2 - f.getlength(value)/(2*scale)) if center else x
        y0=y+h/2-(actual[3]-actual[1])/(2*scale)-actual[1]/scale
        draw.text(xy((x0,y0)),value,font=f,fill="black")
        anchor='middle' if center else 'start'
        sx=x+w/2 if center else x
        weight='bold' if bold else 'normal'
        svg.append(f'<text x="{sx}" y="{y+h/2}" dominant-baseline="central" text-anchor="{anchor}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{weight}" fill="#000000">{html.escape(value)}</text>')
    text(page.title,100,24,page.width-200,42,27,True,True)
    for edge in page.edges:
        pts,r=edge["points"],edge["r"]
        for a,b in zip(pts,pts[1:]):
            line(a,b,r["logical"])
    for name,t in page.tables.items():
        box(t["x"],t["y"],t["w"],t["h"])
        line((t["x"],t["y"]+HEADER),(t["x"]+t["w"],t["y"]+HEADER))
        text(name,t["x"],t["y"],t["w"],HEADER,18,True,True)
        for i,c in enumerate(t["columns"]):
            y=t["y"]+HEADER+PAD+ROW*i
            text("/".join(c.get("keys",[])),t["x"]+12,y,49,ROW,14,True)
            text(c["name"],t["x"]+65,y,t["w"]-76,ROW)
    # End markers sit outside the table; redraw them over the border only after
    # the boxes so the PNG and native draw.io use the same visible notation.
    for edge in page.edges:
        pts,r=edge["points"],edge["r"]
        for kind,a,b in marker_parts(pts[0],pts[1],"optional" if r["optional_parent"] else "one")+marker_parts(pts[-1],pts[-2],"optional" if r["unique_child"] else "many"):
            if kind=="line": line(a,b)
            else:
                draw.ellipse([xy((a[0]-b,a[1]-b)),xy((a[0]+b,a[1]+b))],fill="white",outline="black",width=3)
                svg.append(f'<circle cx="{a[0]}" cy="{a[1]}" r="{b}" fill="#ffffff" stroke="#000000" stroke-width="1.5"/>')
    for l in page.labels:
        text(l["text"],l["x"],l["y"],l["w"],l["h"],l["size"])
    text("PK = Primary key     FK = Foreign key     UK = Unique key     | = One     O = Optional     Crow's foot = Many",100,page.height-39,page.width-200,24,14)
    im.save(OUT / (page.filename + ".png"))
    svg.append("</svg>")
    (OUT / (page.filename + ".svg")).write_text("\n".join(svg),encoding="utf-8")


def write_open_url(xml, filename):
    encoded = urllib.parse.quote(xml, safe="~()*!.'-").encode()
    compressor = zlib.compressobj(9, zlib.DEFLATED, -15)
    data = base64.b64encode(compressor.compress(encoded)+compressor.flush()).decode()
    url = "https://app.diagrams.net/?splash=0&grid=0#create=" + urllib.parse.quote(json.dumps(dict(type="xml",compressed=True,data=data), separators=(",", ":")),safe="")
    tmp = ROOT / "tmp" / "erd"
    tmp.mkdir(parents=True,exist_ok=True)
    (tmp / filename).write_text(url,encoding="utf-8")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--business-only", action="store_true", help="Create a separate ERD with only the eight main business tables.")
    args = parser.parse_args()
    pages = make_pages()
    normalize_routes(pages)
    validate(pages)
    if args.business_only:
        business = copy.deepcopy(pages[1])
        business.name = "Main business tables"
        business.title = "Entity Relationship Diagram - Main Business Tables"
        business.filename = "main-business-erd"
        expected_tables = {"users", "tbl_Category", "tbl_Status", "tbl_Payment_Method",
                           "tbl_Product", "tbl_Stock_in", "tbl_Sale", "tbl_Sold_Item"}
        assert set(business.tables) == expected_tables
        expected_edges = {(r["parent"], r["child"], r["child_column"]) for r in SCHEMA["relationships"]
                          if r["parent"] in expected_tables and r["child"] in expected_tables}
        assert {(e["r"]["parent"], e["r"]["child"], e["r"]["child_column"]) for e in business.edges} == expected_edges
        xml = native_xml([business])
        (OUT / "main-business-erd.drawio").write_text(xml, encoding="utf-8")
        render(business)
        write_open_url(xml, "business-drawio-open-url.txt")
        print(f"Created a separate main business ERD: {len(business.tables)} tables, {sum(len(t['columns']) for t in business.tables.values())} fields and {len(business.edges)} relationships.")
        return
    xml = native_xml(pages)
    (OUT / "updated-erd-2026-10-10.drawio").write_text(xml, encoding="utf-8")
    for page in pages:
        render(page)
    write_open_url(xml, "drawio-open-url.txt")
    print(f"Created {len(pages)} black-and-white pages. First page: {len(pages[0].tables)} unique tables, {sum(len(t['columns']) for t in pages[0].tables.values())} fields and {len(pages[0].edges)} relationships.")


if __name__ == "__main__":
    main()
