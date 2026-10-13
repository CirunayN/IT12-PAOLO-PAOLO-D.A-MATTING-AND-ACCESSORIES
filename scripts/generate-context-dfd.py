"""Create a context diagram balanced with the supplied Figure 3 DFD.

Only documentation artifacts are written. The supplied workflow, rather than
additional application features, determines the external entities and flows.
"""
from __future__ import annotations

import csv
import html
import importlib.util
import json
import math
from pathlib import Path
import sys
import xml.etree.ElementTree as ET

from PIL import Image, ImageDraw

sys.dont_write_bytecode = True

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "dfd"
STEM = "00-context-diagram"
spec = importlib.util.spec_from_file_location("classic_dfd", Path(__file__).with_name("generate-classic-dfd.py"))
classic = importlib.util.module_from_spec(spec)
spec.loader.exec_module(classic)


def textparts(node):
    if node["kind"] == "process":
        return [("0", 12, 0, node["w"] - 24, 60, 27, False),
                (node["title"], 18, 60, node["w"] - 36, node["h"] - 60, 28, False)]
    return [(node["title"], 12, 0, node["w"] - 24, node["h"], 29, False)]


def context():
    diagram = classic.Diagram("context", "Figure 2 - Context Diagram", "Figure 2. Context Diagram", "", 2100, 1490, [])
    diagram.node("system", "process", 800, 290, 460, 540,
                 "Paolo Paolo D.A. Matting\nand Accessories\nManagement System", ref="0")
    classic.external(diagram, "owner", 60, 450, "Owner", 240, 140)
    classic.external(diagram, "customer", 1780, 470, "Customer", 245, 140)
    classic.external(diagram, "labor", 885, 1260, "Labor", 290, 130)

    classic.flow(diagram, "system", "owner", "Overall report\n(sales, services and inventory)",
                 "left", "right", 65 / 540, .15,
                 [(340, 355), (340, 471)], (405, 280), 375, 24)
    classic.flow(diagram, "owner", "system", "Report type, date range\nand print / PDF choice",
                 "right", "left", .38, 200 / 540,
                 [(370, 503.2), (370, 490)], (405, 415), 375, 24)
    classic.flow(diagram, "owner", "system", "Product / category changes,\nimages and shipment details",
                 "right", "left", .6, 335 / 540,
                 [(395, 534), (395, 625)], (405, 550), 375, 24)
    classic.flow(diagram, "owner", "system", "Sales transaction details\n(items, quantities, prices,\nservice request / approval)",
                 "right", "left", .83, 495 / 540,
                 [(340, 566.2), (340, 785)], (405, 680), 375, 24)
    classic.flow(diagram, "customer", "system", "Order and payment details\n(Cash / GCash, tender, reference)",
                 "left", "right", .25, 180 / 540,
                 [(1720, 505), (1720, 470)], (1290, 392), 430, 24)
    classic.flow(diagram, "system", "customer", "Product / service details\nand receipt\n(total, payment and change)",
                 "right", "left", 365 / 540, .75,
                 [(1740, 655), (1740, 575)], (1290, 548), 430, 24)
    classic.flow(diagram, "system", "labor", "Service job details\n(description, items\nand instructions)",
                 "bottom", "top", .25, .25,
                 [(915, 1190), (957.5, 1190)], (520, 990), 350, 24)
    classic.flow(diagram, "labor", "system", "Service status\n(accepted, in progress, completed)\nand completion details",
                 "top", "bottom", .75, .75,
                 [(1102.5, 1210), (1145, 1210)], (1200, 990), 470, 24)
    return diagram


def validate_balance(diagram):
    """Every external Figure 3 flow must appear exactly once in Figure 2."""
    external_entities = {"owner", "customer", "labor"}
    normalize = lambda value: " ".join(value.split())
    expected = []
    for edge in classic.overview().edges:
        if edge["source"] in external_entities or edge["target"] in external_entities:
            source = edge["source"] if edge["source"] in external_entities else "system"
            target = edge["target"] if edge["target"] in external_entities else "system"
            expected.append((source, target, normalize(edge["label"])))
    actual = [(edge["source"], edge["target"], normalize(edge["label"])) for edge in diagram.edges]
    assert sorted(actual) == sorted(expected), "Context flows must balance with the supplied Level 0 DFD."
    assert len(diagram.nodes) == 4 and len(actual) == 8
    classic.textparts = textparts
    classic.validate([diagram])
    for edge in diagram.edges:
        assert all(0 <= x <= diagram.w and 0 <= y <= diagram.h for x, y in edge["points"])


def render(diagram):
    scale = 2
    raster = Image.new("RGB", (diagram.w * scale, diagram.h * scale), "white")
    draw = ImageDraw.Draw(raster)
    svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{diagram.w}" height="{diagram.h}" viewBox="0 0 {diagram.w} {diagram.h}">',
           '<title>Figure 2. Context Diagram</title>',
           '<desc>One system process exchanges the eight external data flows from Figure 3 with Owner, Customer and Labor.</desc>',
           '<defs><marker id="arrow" markerWidth="14" markerHeight="12" refX="12" refY="6" orient="auto" markerUnits="userSpaceOnUse"><path d="M0,0 L12,6 L0,12 L3,6 Z" fill="black"/></marker></defs>',
           f'<rect width="{diagram.w}" height="{diagram.h}" fill="white"/>']

    def rectangle(x, y, width, height, outline=True):
        draw.rectangle((x * scale, y * scale, (x + width) * scale, (y + height) * scale),
                       fill="white", outline="black" if outline else None, width=4)
        svg.append(f'<rect x="{x}" y="{y}" width="{width}" height="{height}" fill="white" stroke="{"black" if outline else "none"}" stroke-width="2"/>')

    def text(x, y, width, height, value, size, bold=False, serif=False):
        face = classic.font(size * scale, bold, serif)
        lines = value.splitlines()
        line_height = size + 5
        top = y + (height - len(lines) * line_height) / 2
        for i, line in enumerate(lines):
            xx = x + (width - face.getlength(line) / scale) / 2
            yy = top + i * line_height
            draw.text((xx * scale, yy * scale), line, font=face, fill="black", anchor="lt")
            family = "Times New Roman, serif" if serif else "Arial, sans-serif"
            svg.append(f'<text x="{xx}" y="{yy + size * .8}" font-family="{family}" font-size="{size}" font-weight="{700 if bold else 400}" fill="black">{html.escape(line)}</text>')

    text(40, 24, diagram.w - 80, 66, diagram.title, 39, True, True)
    for edge in diagram.edges:
        points = edge["points"]
        draw.line([(x * scale, y * scale) for x, y in points], fill="black", width=4)
        a, b = points[-2:]
        angle = math.atan2(b[1] - a[1], b[0] - a[0])
        rear = (b[0] - 12 * math.cos(angle), b[1] - 12 * math.sin(angle))
        triangle = [b, (rear[0] + 5 * math.sin(angle), rear[1] - 5 * math.cos(angle)),
                    (rear[0] - 5 * math.sin(angle), rear[1] + 5 * math.cos(angle))]
        draw.polygon([(x * scale, y * scale) for x, y in triangle], fill="black")
        path = " ".join(("M" if i == 0 else "L") + f"{x},{y}" for i, (x, y) in enumerate(points))
        svg.append(f'<path d="{path}" fill="none" stroke="black" stroke-width="2" marker-end="url(#arrow)"/>')
    for node in diagram.nodes.values():
        x, y, width, height = (node[key] for key in ("x", "y", "w", "h"))
        rectangle(x, y, width, height)
        if node["kind"] == "process":
            draw.line([(x * scale, (y + 60) * scale), ((x + width) * scale, (y + 60) * scale)], fill="black", width=4)
            svg.append(f'<path d="M{x},{y + 60} L{x + width},{y + 60}" stroke="black" stroke-width="2"/>')
        for value, tx, ty, tw, th, size, bold in textparts(node):
            text(x + tx, y + ty, tw, th, value, size, bold)
    for edge in diagram.edges:
        rectangle(edge["lx"], edge["ly"], edge["lw"], edge["lh"], False)
        text(edge["lx"], edge["ly"], edge["lw"], edge["lh"], edge["label"], edge["size"])
    svg.append("</svg>")
    raster.save(OUT / f"{STEM}.png", dpi=(300, 300))
    (OUT / f"{STEM}.svg").write_text("\n".join(svg), encoding="utf-8")


def drawio(diagram):
    mxfile = ET.Element("mxfile", host="app.diagrams.net", type="device", pages="1", compressed="false")
    classic.graphxml(mxfile, diagram)
    root = mxfile.find(".//root")
    for cell in list(root):
        if cell.get("id") == "legend":
            root.remove(cell)
        elif cell.get("id") == "system-header-line":
            cell.find("mxGeometry").set("y", "60")
        elif cell.get("id") == "heading":
            cell.set("style", cell.get("style").replace("fontSize=28", "fontSize=39"))
            geometry = cell.find("mxGeometry")
            geometry.set("y", "24")
            geometry.set("height", "66")
    cells = root.findall("mxCell")
    ids = [cell.get("id") for cell in cells]
    assert len(ids) == len(set(ids))
    for cell in cells:
        for attribute in ("parent", "source", "target"):
            assert cell.get(attribute) is None or cell.get(attribute) in ids
    assert sum(cell.get("edge") == "1" for cell in cells) == 8
    (OUT / f"{STEM}.drawio").write_text(ET.tostring(mxfile, encoding="unicode", xml_declaration=True), encoding="utf-8")


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    diagram = context()
    validate_balance(diagram)
    render(diagram)
    drawio(diagram)
    with (OUT / "00-context-data-flows.csv").open("w", encoding="utf-8-sig", newline="") as file:
        writer = csv.writer(file)
        writer.writerow(["flow_id", "source", "destination", "data"])
        for edge in diagram.edges:
            writer.writerow([edge["id"], edge["source"], edge["target"], " ".join(edge["label"].split())])
    print(json.dumps({"diagram": str(OUT / f"{STEM}.drawio"), "external_entities": 3,
                      "system_processes": 1, "balanced_external_flows": 8}, indent=2))


if __name__ == "__main__":
    main()
