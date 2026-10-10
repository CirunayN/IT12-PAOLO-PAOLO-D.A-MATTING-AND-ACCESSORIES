"""Export the reviewed backup ERD as native, editable draw.io XML.

Uses the same entity placement and relationships as the existing PDF renderer.
Every table, column cell and relationship is a native draw.io object, not an image.
"""

from __future__ import annotations

import base64
from datetime import datetime, timedelta, timezone
import importlib.util
import json
from pathlib import Path
import sys
import urllib.parse
import xml.etree.ElementTree as ET
import zlib


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "erd"
SCALE = 1.7
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location("backup_erd", ROOT / "scripts" / "generate-backup-erd.py")
erd = importlib.util.module_from_spec(spec)
spec.loader.exec_module(erd)
SCHEMA = json.loads((OUT / "schema-metadata.json").read_text(encoding="utf-8"))
FILE = OUT / "updated-erd-2026-10-10.drawio"


def num(value):
    return str(round(value, 4))


def style(**values):
    return "".join(f"{key}={value};" for key, value in values.items())


def vertex(root, ident, value, styling, x, y, width, height, parent="1", **attributes):
    cell = ET.SubElement(root, "mxCell", id=ident, value=value, style=styling, vertex="1", parent=parent, **attributes)
    ET.SubElement(cell, "mxGeometry", x=num(x), y=num(y), width=num(width), height=num(height), **{"as": "geometry"})
    return cell


def text_style(font_size, color=erd.INK, bold=False, align="left", **extra):
    return style(shape="text", html="0", whiteSpace="nowrap", overflow="visible", fillColor="none", strokeColor="none",
                 fontFamily="Helvetica", fontSize=num(font_size), fontColor=color, fontStyle="1" if bold else "0",
                 align=align, verticalAlign="middle", spacing="0", **extra)


class Capture:
    """Collect the existing diagram layout without writing or changing the PDF."""
    def __init__(self):
        self.items = []
        self.boxes = {}
        self.relationships = []

    def rect(self, x, y, w, h, fill="#ffffff", stroke=None, radius=0):
        self.items.append(("rect", (x, y, w, h, fill, stroke, radius)))

    def text(self, x, y, value, size=9, color=erd.INK, bold=False, align="left"):
        self.items.append(("text", (x, y, value, size, color, bold, align)))

    def path(self, points, color=erd.LINE, width=1.1, dashed=False):
        self.items.append(("path", (points, color, width, dashed)))


def capture_entity(scene, box, counts, theme):
    scene.boxes[box[0]] = (box, theme)


def capture_edge(scene, relationship, points, caption=None):
    scene.relationships.append((relationship, points, caption))


def add_decoration(root, capture):
    for i, (kind, data) in enumerate(capture.items):
        ident = f"decoration-{i}"
        if kind == "rect":
            x, y, w, h, fill, stroke, radius = data
            # Canvas background is supplied by mxGraphModel, not a selectable object.
            if x == 0 and y == 0 and w == erd.W and h == erd.H:
                continue
            vertex(root, ident, "", style(shape="rectangle", rounded=int(bool(radius)), arcSize="5", fillColor=fill,
                                           strokeColor=stroke or "none", strokeWidth="1"),
                   x*SCALE, (erd.H-y-h)*SCALE, w*SCALE, h*SCALE)
        elif kind == "text":
            x, y, value, size, color, bold, align = data
            width = erd.stringWidth(value, "Helvetica-Bold" if bold else "Helvetica", size) * 1.08 + 8
            if align == "right":
                x -= width
            vertex(root, ident, value, text_style(size*SCALE, color, bold, align),
                   x*SCALE, (erd.H-y-size*.92)*SCALE, width*SCALE, size*1.35*SCALE, connectable="0")
        elif kind == "path":
            points, color, width, dashed = data
            cell = ET.SubElement(root, "mxCell", id=ident, value="", edge="1", parent="1",
                                 style=style(endArrow="none", startArrow="none", strokeColor=color, strokeWidth=num(width*SCALE), dashed=int(dashed)))
            geo = ET.SubElement(cell, "mxGeometry", relative="1", **{"as": "geometry"})
            for point, key in [(points[0], "sourcePoint"), (points[-1], "targetPoint")]:
                ET.SubElement(geo, "mxPoint", x=num(point[0]*SCALE), y=num((erd.H-point[1])*SCALE), **{"as": key})


def add_table(root, box, theme):
    name, x, top, width = box
    height = erd.entity_height(name)
    table_id = f"table-{name}"
    vertex(root, table_id, name, style(shape="swimlane", html="0", rounded="1", arcSize="5", horizontal="1",
                                      startSize=num(erd.HEADER_H*SCALE), fillColor=theme, swimlaneFillColor="#ffffff",
                                      strokeColor="#cbd5e1", strokeWidth="1.2", swimlaneLine="0", fontFamily="Helvetica",
                                      fontColor="#ffffff", fontStyle="1", fontSize="17.5", align="left", spacingLeft="16",
                                      collapsible="0", container="1", recursiveResize="1"),
           x*SCALE, (erd.H-top)*SCALE, width*SCALE, height*SCALE)
    vertex(root, f"count-{name}", f'{SCHEMA["tables"][name]["row_count"]} rows', text_style(12, "#ffffff", align="right"),
           (width-60)*SCALE, 5*SCALE, 51*SCALE, 18*SCALE, parent=table_id, connectable="0")

    columns = [(9, 32, "KEY"), (44, width-145, "COLUMN"), (width-95, 88, "SQL SERVER TYPE"), (width-16, 8, "?")]
    vertex(root, f"column-heading-{name}", "", style(shape="rectangle", fillColor="#eaf0f6", strokeColor="none", container="1"),
           0, erd.HEADER_H*SCALE, width*SCALE, erd.LABEL_H*SCALE, parent=table_id, connectable="0")
    for i, (left, cell_width, value) in enumerate(columns):
        vertex(root, f"heading-{name}-{i}", value, text_style((6.6 if i == 2 else 6.9)*SCALE, erd.MUTED, True),
               left*SCALE, 0, cell_width*SCALE, erd.LABEL_H*SCALE, parent=f"column-heading-{name}", connectable="0")
    for i, c in enumerate(SCHEMA["tables"][name]["columns"]):
        row_id = f'column-{name}-{c["name"]}'
        vertex(root, row_id, "", style(shape="rectangle", fillColor="#f7f9fb" if i % 2 else "#ffffff",
                                        strokeColor="none", container="1", recursiveResize="1"),
               .5*SCALE, (erd.HEADER_H+erd.LABEL_H+i*erd.ROW_H)*SCALE,
               (width-1)*SCALE, erd.ROW_H*SCALE, parent=table_id)
        key = "/".join(c["keys"])
        field_size = 7.6 if width < 290 else 8
        if erd.stringWidth(c["name"], "Helvetica-Bold" if key else "Helvetica", field_size) > width-144:
            field_size = 6.6
        values = [key, c["name"], c["type"], "Y" if c["nullable"] else "-"]
        sizes = [6.4, field_size, 7.2, 7.1]
        for j, ((left, cell_width, _), value, font_size) in enumerate(zip(columns, values, sizes)):
            vertex(root, f"{row_id}-cell-{j}", value,
                   text_style(font_size*SCALE, theme if j == 0 else erd.INK if j == 1 else erd.MUTED, bool(key) and j < 2),
                   left*SCALE, 0, cell_width*SCALE, erd.ROW_H*SCALE, parent=row_id, connectable="0")


def connection_id(box, column, point):
    if abs(point[1] - erd.row_y(box, column)) < .01:
        return f"column-{box[0]}-{column}"
    return f"table-{box[0]}"


def add_relationship(root, capture, r, points, caption, index):
    start, end = points[0], points[-1]
    parent_box = capture.boxes[r["parent"]][0]
    child_box = capture.boxes[r["child"]][0]
    source = connection_id(parent_box, r["parent_column"], start)
    target = connection_id(child_box, r["child_column"], end)
    source_x = 1 if start[0] > parent_box[1] else 0
    target_x = 1 if end[0] > child_box[1] else 0
    source_y = (parent_box[2]-start[1])/erd.entity_height(r["parent"]) if source.startswith("table-") else .5
    target_y = (child_box[2]-end[1])/erd.entity_height(r["child"]) if target.startswith("table-") else .5
    color = erd.PURPLE if r["logical"] else erd.LINE
    edge_style = style(edgeStyle="segmentEdgeStyle", rounded="0", html="0", strokeColor=color, strokeWidth="1.8",
                       startArrow="ERzeroToOne" if r["optional_parent"] else "ERmandOne",
                       endArrow="ERzeroToOne" if r["unique_child"] else "ERzeroToMany",
                       startFill="0", endFill="0", startSize="14", endSize="14",
                       dashed=int(r["logical"]), dashPattern="6 4", jumpStyle="gap", jumpSize="8",
                       exitX=source_x, exitY=num(source_y), exitPerimeter="0",
                       entryX=target_x, entryY=num(target_y), entryPerimeter="0",
                       followTerminals="1", fontColor=color, fontSize="12.7")
    ident = f'relationship-{index}-{r["child"]}-{r["child_column"]}'
    cell = ET.SubElement(root, "mxCell", id=ident, value="", style=edge_style, edge="1", parent="1", source=source, target=target)
    geo = ET.SubElement(cell, "mxGeometry", relative="1", **{"as": "geometry"})
    waypoints = ET.SubElement(geo, "Array", **{"as": "points"})
    for x, y in points[1:-1]:
        ET.SubElement(waypoints, "mxPoint", x=num(x*SCALE), y=num((erd.H-y)*SCALE))
    # Keep relationship labels attached to the connector, so moving an edge keeps
    # its name; an offset places them at the same clear location as the PDF.
    if caption:
        x, y, text = caption
        label = vertex(root, ident+"-label", text,
                       text_style(7.5*SCALE, color, labelBackgroundColor="#f4f7fa"),
                       0, 0, (erd.stringWidth(text,"Helvetica",7.5)+10)*SCALE, 13*SCALE,
                       parent=ident, connectable="0")
        label_geo = label.find("mxGeometry")
        label_geo.set("relative", "1")
        lengths = [((b[0]-a[0])**2+(b[1]-a[1])**2)**.5 for a,b in zip(points,points[1:])]
        distance = sum(lengths)/2
        for a,b,length in zip(points,points[1:],lengths):
            if distance <= length:
                fraction = distance/length
                midpoint = ((a[0]+fraction*(b[0]-a[0]))*SCALE, (erd.H-a[1]-fraction*(b[1]-a[1]))*SCALE)
                break
            distance -= length
        ET.SubElement(label_geo,"mxPoint",x=num(x*SCALE-midpoint[0]),
                      y=num((erd.H-y-9)*SCALE-midpoint[1]), **{"as":"offset"})


def validate(mxfile):
    seen_tables = set()
    seen_columns = set()
    links = 0
    for diagram in mxfile.findall("diagram"):
        root = diagram.find("mxGraphModel/root")
        cells = {c.get("id"): c for c in root.findall("mxCell")}
        assert len(cells) == len(root), "Duplicate cell IDs"
        assert "0" in cells and cells["1"].get("parent") == "0"
        for ident, cell in cells.items():
            if ident == "0":
                continue
            assert cell.get("parent") in cells
            if ident == "1":
                continue
            assert (cell.get("vertex") == "1") != (cell.get("edge") == "1")
            geometry = cell.find("mxGeometry")
            assert geometry is not None
            if cell.get("edge") == "1":
                assert geometry.get("relative") == "1"
                if cell.get("source"):
                    assert cells[cell.get("source")].get("vertex") == "1"
                    assert cells[cell.get("target")].get("vertex") == "1"
                    links += 1
            else:
                assert float(geometry.get("width")) > 0 and float(geometry.get("height")) > 0
            if ident.startswith("table-"):
                seen_tables.add(ident[6:])
            if ident.startswith("column-") and not ident.startswith("column-heading-") and "-cell-" not in ident:
                name = ident[7:]
                seen_columns.add(name)
                table, column = next((t, c["name"]) for t, v in SCHEMA["tables"].items() for c in v["columns"] if t+"-"+c["name"] == name)
                vals = [cells[f"{ident}-cell-{i}"].get("value") for i in range(4)]
                c = next(c for c in SCHEMA["tables"][table]["columns"] if c["name"] == column)
                assert vals == ["/".join(c["keys"]), c["name"], c["type"], "Y" if c["nullable"] else "-"]
    assert seen_tables == set(SCHEMA["tables"])
    assert len(seen_columns) == SCHEMA["column_count"]
    assert links == len(SCHEMA["relationships"])
    assert not any("image=" in c.get("style", "") for c in mxfile.iter("mxCell"))
    return dict(pages=len(mxfile.findall("diagram")), tables=len(seen_tables), columns=len(seen_columns),
                native_relationships=links, embedded_images=0, validation="passed")


def main():
    assert erd.TABLES == {n: v["columns"] for n, v in SCHEMA["tables"].items()}
    assert erd.RELS == SCHEMA["relationships"]
    mxfile = ET.Element("mxfile", host="app.diagrams.net", agent="Paolo Paolo ERD generator", type="device", pages="3", compressed="false")
    counts = {n: v["row_count"] for n,v in SCHEMA["tables"].items()}
    created = datetime.fromisoformat(SCHEMA["created_at"]).astimezone(timezone(timedelta(hours=8))).strftime("%Y-%m-%d %H:%M:%S +08:00")
    erd.entity = capture_entity
    erd.edge = capture_edge
    pages = [
        ("business", "Inventory and sales", "Inventory & sales", "The eight core entities for products, stock batches, sales and staff.", erd.business),
        ("security", "Accounts and security", "Accounts & security", "Account management, recovery and session relationships, including optional references.", erd.security),
        ("framework", "Laravel supporting tables", "Framework tables", "Laravel cache, queues and migration bookkeeping. No declared FK relationships.", erd.infrastructure),
    ]
    for page_number, (ident, page_name, title, subtitle, draw) in enumerate(pages,1):
        capture = Capture()
        erd.header(capture,page_number,title,subtitle,created,SCHEMA["column_count"])
        draw(capture,counts)
        diagram = ET.SubElement(mxfile,"diagram",id=ident,name=page_name)
        model = ET.SubElement(diagram,"mxGraphModel",dx="1800",dy="1100",grid="0",gridSize="10",guides="1",tooltips="1",
                              connect="1",arrows="1",fold="1",page="1",pageScale="1",pageWidth=num(erd.W*SCALE),
                              pageHeight=num(erd.H*SCALE),math="0",shadow="0",background="#f4f7fa",adaptiveColors="none")
        root = ET.SubElement(model,"root")
        ET.SubElement(root,"mxCell",id="0")
        ET.SubElement(root,"mxCell",id="1",parent="0")
        add_decoration(root,capture)
        for box,theme in capture.boxes.values():
            add_table(root,box,theme)
        for i,(r,points,caption) in enumerate(capture.relationships):
            add_relationship(root,capture,r,points,caption,i)
    result = validate(mxfile)
    ET.indent(mxfile, space="  ")
    xml = ET.tostring(mxfile,encoding="utf-8",xml_declaration=True)
    FILE.write_bytes(xml)
    # URL fragment is a temporary convenience for verifying the local file in
    # the official editor. Only schema objects, not backup records, are included.
    encoded = urllib.parse.quote(xml.decode(),safe="~()*!.'-").encode()
    compressor = zlib.compressobj(9,zlib.DEFLATED,-15)
    data = base64.b64encode(compressor.compress(encoded)+compressor.flush()).decode()
    payload = json.dumps(dict(type="xml",compressed=True,data=data),separators=(",",":"))
    url = "https://app.diagrams.net/?splash=0&grid=0#create=" + urllib.parse.quote(payload,safe="")
    tmp = ROOT / "tmp" / "erd"
    tmp.mkdir(parents=True,exist_ok=True)
    (tmp/"drawio-open-url.txt").write_text(url,encoding="utf-8")
    print(json.dumps(dict(file=str(FILE),bytes=len(xml),**result)))


if __name__ == "__main__":
    main()
