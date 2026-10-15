"""Create an expanded Level 0 with one process for each distinct operation.

The first draw.io page contains the entire DFD. Six focus pages use the same
process numbers and are crops of its module areas, not additional DFD levels.
No application files or database records are changed. Retained service flows
are conceptual business flows from the earlier diagram, marked with an *.
"""
from __future__ import annotations

import copy
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
STEM = "Level-0-DFD-Dissected"
spec = importlib.util.spec_from_file_location("matched_dfd", Path(__file__).with_name("generate-use-case-matched-dfd.py"))
matched = importlib.util.module_from_spec(spec)
spec.loader.exec_module(matched)
classic = matched.classic

OWNER = "Admin/Owner"
CASHIER = "Cashier"
CUSTOMER = "Customer"
LABOR = "Labor"
STORES = {
    "D1": ("Product Records", "Catalog, prices, stock batches\nand remaining quantities"),
    "D2": ("Product Sale Records", "Sale, cashier, items, totals\nand Cash/GCash payment details"),
    "D3": ("Service Sale Records*", "Service description, charges\njob status and sale link"),
    "D4": ("User Account Records", "Username, password hash, role\naccess status and sessions"),
    "D5": ("Database Backup Files", "Saved snapshots and\nselected restoration files"),
    "D6": ("System Database", "Aggregate of D1-D4 records\nfor backup and restoration"),
}


def actor(name, incoming=None, outgoing=None):
    return (name, incoming, outgoing)


def store(ref, incoming=None, outgoing=None):
    """incoming is a store read; outgoing is a store write."""
    return (ref, incoming, outgoing)


def op(number, title, body, actors=(), stores=()):
    return dict(id=f"{number}.0", title=title, body=body, actors=actors, stores=stores)


MODULES = [
    ("accounts", "Accounts and Access", [
        op(1, "Log In", "Check credentials and account access", [
            actor(OWNER, "Admin username\nand password", "Login result and\nassigned interface"),
            actor(CASHIER, "Cashier username\nand password", "Login result and\ncashier interface"),
        ], [store("D4", "Stored credentials, role\nand account access status", "Authenticated\nsession record")]),
        op(2, "Create User Account", "Validate and save a new account", [
            actor(OWNER, "New user details\nand admin password", "Created account details\nor validation result"),
        ], [store("D4", "Existing usernames and\nadmin account credentials", "New user account\nand assigned role")]),
        op(3, "Enable or Disable\nUser Account", "Change the selected account's access", [
            actor(OWNER, "Selected user ID\nand requested access status", "Updated account\naccess status"),
        ], [store("D4", "Selected account\nand active sessions", "Updated access status\nand session records")]),
    ]),
    ("inventory", "Inventory", [
        op(4, "Add Product", "Validate and save product details", [
            actor(OWNER, "New product details\nand category selection", "Saved product details\nor validation result"),
        ], [store("D1", "Existing product/category\ninformation", "New product\nrecord")]),
        op(5, "Update Product", "Save changes to an existing product", [
            actor(OWNER, "Product ID and\nupdated product values", "Updated product details\nor validation result"),
        ], [store("D1", "Existing product record\nand low-stock threshold", "Updated product record\nand threshold")]),
        op(6, "View Inventory", "Apply filters and display current stock", [
            actor(OWNER, "Name/date/price/stock filters\nand sort direction", "Filtered inventory\nand current stock levels"),
        ], [store("D1", "Product details, prices\nand available quantities")]),
        op(7, "Record Incoming Stock", "Record the received stock batch", [
            actor(OWNER, "Product, received quantity,\nprices and batch details", "Recorded incoming stock\nand receiving confirmation"),
        ], [store("D1", "Selected product\nand existing stock", "New receiving batch\nand updated stock balance")]),
    ]),
    ("sales", "Sales", [
        op(8, "Validate Sales Order", "Check selected items and availability", [
            actor(OWNER, "Selected products\nand quantities", "Order validation result\nor item-availability errors"),
            actor(CASHIER, "Selected products\nand quantities", "Order validation result\nor item-availability errors"),
            actor(CUSTOMER, "Customer order\nand requested quantities"),
        ], [store("D1", "Available products,\nquantities and selling prices")]),
        op(9, "Calculate Sales Total", "Calculate line amounts and total due", [
            actor(OWNER, None, "Calculated order amounts\nand total due"),
            actor(CASHIER, None, "Calculated order amounts\nand total due"),
        ]),
        op(10, "Verify Payment", "Check amount and payment information", [
            actor(OWNER, "Payment method, tender\nand GCash reference", "Payment validation result\nor payment errors"),
            actor(CASHIER, "Payment method, tender\nand GCash reference", "Payment validation result\nor payment errors"),
            actor(CUSTOMER, "Customer payment details\n(Cash or GCash)"),
        ]),
        op(11, "Record Sales Transaction", "Save the sale and purchased items", [], [
            store("D2", None, "Sale, cashier, items, totals\nand accepted payment details"),
        ]),
        op(12, "Update Stock After Sale", "Apply the recorded sold quantities", [
            actor(OWNER, None, "Completed sale ID\nand stock-update result"),
            actor(CASHIER, None, "Completed sale ID\nand stock-update result"),
        ], [store("D1", "Eligible stock batches\nand remaining quantities", "Deducted stock quantities\nand updated batch balances")]),
        op(13, "View Sales History", "Cashier can view only their own sales", [
            actor(OWNER, "Sales-history filters\nand selected transaction", "Matching sales history\nand transaction details"),
            actor(CASHIER, "Own-sales filters\nand selected transaction", "Own sales history\nand transaction details"),
        ], [store("D2", "Sales records filtered\nby authorized cashier scope")]),
        op(14, "Print Sales Receipt", "Prepare the selected transaction receipt", [
            actor(OWNER, "Selected sale ID\nand print request", "Printable sales\nreceipt"),
            actor(CASHIER, "Selected sale ID\nand print request", "Printable sales\nreceipt"),
            actor(CUSTOMER, None, "Purchase receipt: items,\ntotal, payment and change"),
        ], [store("D2", "Saved sale, items, totals\nand payment/change details")]),
    ]),
    ("reports", "Reports", [
        op(15, "Generate Sales Report", "Summarize sales for the chosen period", [
            actor(OWNER, "Sales-report date range\nand filters", "Generated sales report\nand sales totals"),
        ], [store("D2", "Sales data within\nthe selected date range"),
            store("D3", "Retained service-sale data*\nwithin the reporting period")]),
        op(16, "Generate Inventory Report", "Summarize inventory and stock status", [
            actor(OWNER, "Inventory-report filters\nand selected criteria", "Generated inventory report\nand stock-status summary"),
        ], [store("D1", "Product details, stock levels\nand low-stock thresholds")]),
        op(17, "Print or Download Reports", "Prepare the chosen report output", [
            actor(OWNER, "Selected report\nand print/download choice", "Printable report\nor downloadable report file"),
        ]),
    ]),
    ("backup", "Database Backup and Restore", [
        op(18, "Create Database Backup", "Create and save a database snapshot", [
            actor(OWNER, "Database-backup request\nand destination details", "Saved backup file details\nand backup confirmation"),
        ], [store("D6", "Current database\nrecords for backup"),
            store("D5", None, "New database snapshot\nand backup file details")]),
        op(19, "Restore Database Backup", "Validate access and restore saved data", [
            actor(OWNER, "Selected backup file\nand administrator password", "Database-restoration result\nor validation errors"),
        ], [store("D5", "Selected database\nbackup contents"),
            store("D4", "Admin credentials\nand administrator role"),
            store("D6", None, "Validated database records\nrestored from the backup")]),
    ]),
    ("services", "Retained Service Business Processes*", [
        op(20, "Record Service Request*", "Record the requested work and charge", [
            actor(OWNER, "Service description, charge\nand linked transaction ID*", "Recorded service request\nand job reference*"),
            actor(LABOR, None, "Service job details\nand work instructions*"),
        ], [store("D3", None, "Service request, charge\ntransaction link and job status*")]),
        op(21, "Record Service Status*", "Record job progress or completion", [
            actor(LABOR, "Service progress\nand completion details*", "Recorded service\nstatus confirmation*"),
            actor(OWNER, None, "Updated service status\nand completion information*"),
        ], [store("D3", "Existing service job\nand transaction link*", "Updated service status\nand completion details*")]),
    ]),
]

# Each supplied use case maps to distinct processes. Checkout is decomposed
# further so order validation, calculation, payment, saving and stock updates
# do not share a single broad Sales process.
USE_CASE_MAPPING = [
    ("Log In", "1.0"), ("Create User Account", "2.0"),
    ("Enable or Disable User Account", "3.0"), ("Add Product", "4.0"),
    ("Update Product", "5.0"), ("View Inventory", "6.0"),
    ("Record Incoming Stock", "7.0"),
    ("Process Sales Transaction", "8.0, 9.0, 10.0, 11.0, 12.0"),
    ("View Sales History", "13.0"), ("Print Sales Receipt", "14.0"),
    ("Generate Sales Report", "15.0"), ("Generate Inventory Report", "16.0"),
    ("Print or Download Reports", "17.0"),
    ("Create Database Backup", "18.0"), ("Restore Database Backup", "19.0"),
]


def textparts(node):
    w, h = node["w"], node["h"]
    if node["kind"] == "process":
        return [(node["ref"], 8, 0, w-16, 27, 20, True),
                (node["title"], 10, 33, w-20, 61, 24, True),
                (node["body"], 12, 104, w-24, h-111, 18, False)]
    if node["kind"] == "external":
        return [(node["title"], 10, 0, w-20, h, 24, True)]
    return [(node["ref"], 2, 0, 49, h, 22, True),
            (node["title"], 64, 8, w-78, 35, 22, True),
            (node["body"], 64, 47, w-78, h-55, 17, False)]


classic.textparts = textparts


def process_link(d, source, target, label, route_lane=0):
    """Route process-to-process data in the clear central vertical column."""
    source_node, target_node = d.nodes[source], d.nodes[target]
    adjacent = target_node["y"] - (source_node["y"]+source_node["h"]) < 750
    target_x = target_node["x"] + target_node["w"] * (.45 if route_lane == 0 else .72)
    if adjacent:
        a = d.point(source, "bottom", .45)
        b = d.point(target, "top", .45)
        label_y = target_node["row_start"] + 10
        via = [(a[0], label_y+20), (b[0], label_y+20)]
        sf, tf = .45, .45
    else:
        a, b = d.point(source, "bottom", .72), d.point(target, "top", .72)
        # Bypass intervening operations outside the data-store column. This
        # keeps the long connection from sharing a line with store reads.
        bridge_x = 2555 + route_lane * 10
        label_y = target_node["row_start"] - 70 - route_lane * 62
        bridge_y = source_node["row_end"] + 35
        via = [(a[0], bridge_y), (bridge_x, bridge_y), (bridge_x, label_y+25),
               (target_x, label_y+25)]
        sf, tf = .72, .72
    classic.flow(d, source, target, label,
                 "bottom", "top", sf, tf, via,
                 (945, label_y), 445, 17)


def build_module(key, title, operations):
    d = classic.Diagram(f"dissected-{key}", f"{title} - process focus",
                        title, "", 2600, 1000, [])
    cursor = 150
    for item in operations:
        actor_count, store_count = len(item["actors"]), len(item["stores"])
        slots = max(actor_count, store_count, 1)
        row_height = slots * 190 + 190
        process_y = cursor + (row_height-175)/2
        classic.process(d, item["id"], 930, process_y, item["title"], item["body"], 460, 175)
        d.nodes[item["id"]]["row_start"] = cursor
        d.nodes[item["id"]]["row_end"] = cursor + row_height
        d.nodes[item["id"]]["module"] = key
        for index, (name, incoming, outgoing) in enumerate(item["actors"]):
            ident = f"{key}-{item['id']}-actor-{index}"
            actor_y = cursor + 70 + index*190
            classic.external(d, ident, 60, actor_y, name, 280, 90)
            input_fraction = .12 + index * (.63/max(actor_count-1, 1))
            output_fraction = min(.95, input_fraction+.15)
            if incoming:
                matched.left_flow(d, ident, item["id"], incoming, True,
                                  cursor+40+index*190, input_fraction, index)
            if outgoing:
                matched.left_flow(d, ident, item["id"], outgoing, False,
                                  cursor+150+index*190, output_fraction, index)
        for index, (ref, incoming, outgoing) in enumerate(item["stores"]):
            ident = f"{key}-{item['id']}-store-{ref}-{index}"
            title_text, body = STORES[ref]
            store_y = cursor + 60 + index*190
            classic.store(d, ident, ref, 2070, store_y, title_text, body, 470, 130)
            input_fraction = .12 + index * (.63/max(store_count-1, 1))
            output_fraction = min(.95, input_fraction+.15)
            if incoming:
                matched.right_flow(d, item["id"], ident, incoming, False,
                                   cursor+40+index*190, input_fraction, index)
            if outgoing:
                matched.right_flow(d, item["id"], ident, outgoing, True,
                                   cursor+150+index*190, output_fraction, index)
        cursor += row_height + 85
    d.h = cursor + 170

    if key == "sales":
        process_link(d, "8.0", "9.0", "Validated order:\nitems, quantities and unit prices")
        process_link(d, "9.0", "10.0", "Calculated order amounts\nand total due")
        process_link(d, "10.0", "11.0", "Order and accepted payment:\nmethod, tender, reference and change")
        process_link(d, "11.0", "12.0", "Recorded sale ID\nand quantities sold")
        process_link(d, "12.0", "14.0", "Confirmed sale ID\nfor receipt preparation", 1)
        process_link(d, "13.0", "14.0", "Selected history transaction ID\nfor receipt printing")
    if key == "reports":
        process_link(d, "15.0", "17.0", "Generated sales-report data\nfor selected output", 1)
        process_link(d, "16.0", "17.0", "Generated inventory-report data\nfor selected output")
    return d


def validate_module(d):
    classic.validate([d])
    for edge in d.edges:
        assert all(0 <= x <= d.w and 0 <= y <= d.h for x, y in edge["points"])
    for node in d.nodes.values():
        assert node["x"] >= 0 and node["y"] >= 0
        assert node["x"]+node["w"] <= d.w and node["y"]+node["h"] <= d.h


def merged_overview(modules):
    module_by_key = {d.id.removeprefix("dissected-"): d for d in modules}
    columns = [["accounts", "inventory", "reports", "backup"], ["sales", "services"]]
    result = classic.Diagram("level-0-dissected", "Level 0 - all separate processes",
                            "Figure 3. Level 0 Data Flow Diagram — Separate Processes", "", 5300, 1000, [])
    headings = []
    max_bottom = 0
    for column_index, keys in enumerate(columns):
        x_offset = 20 + column_index * 2640
        y_offset = 180
        for key in keys:
            module = module_by_key[key]
            headings.append((module.title, x_offset+50, y_offset, 2500, 80))
            mapping = {ident: f"{key}-{ident}" for ident in module.nodes}
            for ident, node in module.nodes.items():
                translated = copy.deepcopy(node)
                translated["id"] = mapping[ident]
                translated["x"] += x_offset
                translated["y"] += y_offset
                result.nodes[translated["id"]] = translated
            for edge in module.edges:
                translated = copy.deepcopy(edge)
                translated["id"] = f"overview-{edge['id']}"
                translated["source"], translated["target"] = mapping[edge["source"]], mapping[edge["target"]]
                translated["points"] = [(x+x_offset, y+y_offset) for x, y in edge["points"]]
                translated["lx"] += x_offset
                translated["ly"] += y_offset
                result.edges.append(translated)
            y_offset += module.h - 100
        max_bottom = max(max_bottom, y_offset)
    result.h = max_bottom + 240
    result.headings = headings
    return result


LEGEND = "Rectangle = external entity  |  Numbered box = separate process  |  D1-D6 = logical data stores  |  Arrow = data flow"
NOTE = "Repeated actors/stores are the same entities. Cashier history is limited to their own transactions."
SERVICE_NOTE = "*Labor and service records are retained from the earlier business DFD and are not in the supplied use case."


def graph_xml(mxfile, d):
    classic.graphxml(mxfile, d)
    root = mxfile.findall("diagram")[-1].find("mxGraphModel/root")
    heading = root.find("mxCell[@id='heading']")
    heading_geometry = heading.find("mxGeometry")
    heading_geometry.set("height", "75")
    if hasattr(d, "title_size"):
        heading.set("style", heading.get("style").replace("fontSize=28", f"fontSize={d.title_size}"))
    root.find("mxCell[@id='legend']").set("value", LEGEND)
    note_size = getattr(d, "note_size", 17)
    note_height = max(25, note_size+10)
    note_offsets = (170, 115) if note_size >= 30 else (130, 90) if note_size > 17 else (115, 85)
    classic.textcell(root, "entity-note", NOTE, 40, d.h-note_offsets[0], d.w-80, note_height, note_size)
    classic.textcell(root, "service-note", SERVICE_NOTE, 40, d.h-note_offsets[1], d.w-80, note_height, note_size)
    if hasattr(d, "legend_size"):
        legend = root.find("mxCell[@id='legend']")
        legend.set("style", legend.get("style").replace("fontSize=14", f"fontSize={d.legend_size}"))
        legend.find("mxGeometry").set("height", str(max(27, d.legend_size+10)))
        legend.find("mxGeometry").set("y", str(d.h-60 if d.legend_size >= 25 else d.h-47))
    for node in d.nodes.values():
        if node["kind"] == "process" and hasattr(d, "process_header_height"):
            root.find(f"mxCell[@id='{node['id']}-header-line']/mxGeometry").set("y", str(d.process_header_height))
        if node["kind"] == "store" and hasattr(d, "store_partition_width"):
            root.find(f"mxCell[@id='{node['id']}-partition']/mxGeometry").set("x", str(d.store_partition_width))
    if hasattr(d, "stroke_width"):
        for cell in root.findall("mxCell"):
            cell.set("style", cell.get("style", "").replace("strokeWidth=1.5", f"strokeWidth={d.stroke_width}")
                     .replace("jumpSize=8", "jumpSize=16"))
    for index, (title, x, y, width, height) in enumerate(getattr(d, "headings", [])):
        classic.textcell(root, f"module-heading-{index}", title, x, y, width, height, 34, True)
    ids = [cell.get("id") for cell in root.findall("mxCell")]
    assert len(ids) == len(set(ids))
    for cell in root.findall("mxCell"):
        for attr in ("parent", "source", "target"):
            assert cell.get(attr) is None or cell.get(attr) in ids


def render(d, basename, scale=1):
    """Render the same geometry as the draw.io cells, with bounded raster size."""
    raster = Image.new("RGB", (round(d.w*scale), round(d.h*scale)), "white")
    draw = ImageDraw.Draw(raster)
    stroke_width = getattr(d, "stroke_width", 1.5)
    svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{d.w}" height="{d.h}" viewBox="0 0 {d.w} {d.h}">',
           '<defs><marker id="arrow" markerWidth="11" markerHeight="10" refX="10" refY="5" orient="auto" markerUnits="userSpaceOnUse"><path d="M0,0 L10,5 L0,10 L2,5 Z" fill="black"/></marker></defs>',
           f'<rect width="{d.w}" height="{d.h}" fill="white"/>']

    def rectangle(x, y, width, height, stroke=True):
        draw.rectangle((x*scale, y*scale, (x+width)*scale, (y+height)*scale), fill="white",
                       outline="black" if stroke else None, width=max(1, round(stroke_width*scale)))
        svg.append(f'<rect x="{x}" y="{y}" width="{width}" height="{height}" fill="white" stroke="{"black" if stroke else "none"}" stroke-width="{stroke_width}"/>')

    def line(a, b):
        draw.line([(x*scale, y*scale) for x, y in [a, b]], fill="black", width=max(1, round(stroke_width*scale)))
        svg.append(f'<path d="M{a[0]},{a[1]} L{b[0]},{b[1]}" fill="none" stroke="black" stroke-width="{stroke_width}"/>')

    def text(x, y, width, height, value, size, bold=False, serif=False):
        face = classic.font(max(1, size*scale), bold, serif)
        lines = value.splitlines()
        lh = size+5
        top = y+(height-len(lines)*lh)/2
        for index, value_line in enumerate(lines):
            xx = x+(width-face.getlength(value_line)/scale)/2
            yy = top+index*lh
            draw.text((xx*scale, yy*scale), value_line, font=face, fill="black")
            svg.append(f'<text x="{xx}" y="{yy+size}" font-family="{"Times New Roman, serif" if serif else "Arial, sans-serif"}" font-size="{size}" font-weight="{700 if bold else 400}">{html.escape(value_line)}</text>')

    text(40, 15, d.w-80, 75, d.title, getattr(d, "title_size", 28), True, True)
    for edge in d.edges:
        points = edge["points"]
        draw.line([(x*scale, y*scale) for x, y in points], fill="black", width=max(1, round(stroke_width*scale)))
        a, b = points[-2:]
        angle = math.atan2(b[1]-a[1], b[0]-a[0])
        rear = b[0]-11*math.cos(angle), b[1]-11*math.sin(angle)
        triangle = [b, (rear[0]+4.5*math.sin(angle), rear[1]-4.5*math.cos(angle)),
                    (rear[0]-4.5*math.sin(angle), rear[1]+4.5*math.cos(angle))]
        draw.polygon([(x*scale, y*scale) for x, y in triangle], fill="black")
        path = " ".join(("M" if index == 0 else "L")+f"{x},{y}" for index, (x, y) in enumerate(points))
        svg.append(f'<path d="{path}" fill="none" stroke="black" stroke-width="{stroke_width}" marker-end="url(#arrow)"/>')
    for node in d.nodes.values():
        x, y, width, height = (node[k] for k in ("x", "y", "w", "h"))
        rectangle(x, y, width, height, node["kind"] != "store")
        if node["kind"] == "process":
            header_y = y+getattr(d, "process_header_height", 28)
            line((x, header_y), (x+width, header_y))
        if node["kind"] == "store":
            for a, b in [((x,y),(x+width,y)), ((x,y+height),(x+width,y+height)),
                         ((x,y),(x,y+height)), ((x+getattr(d, "store_partition_width", 53),y),
                                              (x+getattr(d, "store_partition_width", 53),y+height))]:
                line(a, b)
        for value, xx, yy, ww, hh, size, bold in textparts(node):
            text(x+xx, y+yy, ww, hh, value, size, bold)
    for edge in d.edges:
        rectangle(edge["lx"], edge["ly"], edge["lw"], edge["lh"], False)
        text(edge["lx"], edge["ly"], edge["lw"], edge["lh"], edge["label"], edge["size"])
    for title, x, y, width, height in getattr(d, "headings", []):
        text(x, y, width, height, title, 34, True)
    note_size = getattr(d, "note_size", 17)
    note_height = max(25, note_size+10)
    note_offsets = (170, 115) if note_size >= 30 else (130, 90) if note_size > 17 else (115, 85)
    text(40, d.h-note_offsets[0], d.w-80, note_height, NOTE, note_size)
    text(40, d.h-note_offsets[1], d.w-80, note_height, SERVICE_NOTE, note_size)
    legend_size = getattr(d, "legend_size", 14)
    text(40, d.h-60 if legend_size >= 25 else d.h-47, d.w-80, max(27, legend_size+10), LEGEND, legend_size)
    svg.append("</svg>")
    raster.save(OUT/f"{basename}.png")
    (OUT/f"{basename}.svg").write_text("\n".join(svg), encoding="utf-8")


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    modules = [build_module(key, title, operations) for key, title, operations in MODULES]
    for module in modules:
        validate_module(module)
    overview = merged_overview(modules)
    classic.validate([overview])
    process_numbers = {node["ref"] for node in overview.nodes.values() if node["kind"] == "process"}
    assert process_numbers == {f"{number}.0" for number in range(1, 22)}
    entities = {node["title"] for node in overview.nodes.values() if node["kind"] == "external"}
    assert entities == {OWNER, CASHIER, CUSTOMER, LABOR}
    retained_stores = {node["ref"] for node in overview.nodes.values() if node["kind"] == "store"}
    assert retained_stores == set(STORES)
    assert {row[0] for row in USE_CASE_MAPPING} == {row[0] for row in matched.USE_CASES}

    mxfile = ET.Element("mxfile", host="app.diagrams.net", type="device", pages="7", compressed="false")
    graph_xml(mxfile, overview)
    for module in modules:
        graph_xml(mxfile, module)
    ET.indent(mxfile)
    target = OUT/f"{STEM}.drawio"
    ET.ElementTree(mxfile).write(target, encoding="utf-8", xml_declaration=True)
    # Full diagram is rendered at 0.6x for a compact overview; focus previews
    # retain their original readable scale. SVGs preserve all vector detail.
    render(overview, STEM, .6)
    for module in modules:
        render(module, f"{STEM}-{module.id.removeprefix('dissected-')}", 1)

    with (OUT/f"{STEM}-flows.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["module", "flow_id", "source", "destination", "data"])
        for module in modules:
            for edge in module.edges:
                writer.writerow([module.title, edge["id"], edge["source"], edge["target"], edge["label"].replace("\n", " ")])
    with (OUT/f"{STEM}-use-cases.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["supplied_use_case", "separate_process_numbers"])
        writer.writerows(USE_CASE_MAPPING)
    lines = ["# Level 0 DFD with separate processes", "",
             "The first draw.io page is the complete expanded Level 0. The six remaining pages are "
             "focus views of the same diagram for reading and printing, with identical process numbers. "
             "They are not child decompositions or additional DFD levels. Every numbered box performs "
             "one distinct operation with labelled inputs and outputs. A process may have several "
             "related input/output arrows; it is separated by the operation it performs.", "",
             "## Processes", "", "| Area | Separate processes |", "| --- | --- |"]
    for key, title, operations in MODULES:
        descriptions = "; ".join(f"{operation['id']} {operation['title'].replace(chr(10), ' ')}" for operation in operations)
        lines.append(f"| {title} | {descriptions} |")
    lines += ["", "## Scope and consistency", "",
              "Admin/Owner, Cashier, Customer and Labor remain. D1 Product Records, D2 Product Sale "
              "Records and D3 Service Sale Records are retained, alongside D4 accounts, D5 backup files "
              "and the D6 aggregate database state. Repeated rectangles identify the same actor/store. "
              "D6 is not another physical database.", "",
              "All 15 supplied use cases map to the 19 supported-system operations. The Process Sales "
              "Transaction use case is split into order validation, amount calculation, payment checking, "
              "saving the sale and deducting stock. Receipt printing and history retrieval have their own "
              "processes. Cashier history is limited to their own transactions. These are logical steps; "
              "saving a sale and updating stock still belong to the same atomic checkout in the application.", "",
              "The two marked service processes (20.0 and 21.0) retain the original Labor/service "
              "business flows. They are outside the supplied use case and are not claims of implemented "
              "Laravel service functions. Customer and Labor are business data sources/recipients, not "
              "login actors in the supplied use case. Payment checking describes validation of recorded "
              "payment information, not an automated connection to GCash.", "",
              "The preceding grouped Level 0, context and older Level 1 files are preserved. Earlier "
              "context/Level 1 pages require corresponding scope and numbering revisions before inclusion "
              "with this expanded Level 0 in the final document.", "",
              "No application source or database rows were changed. Regenerate documentation with "
              "`scripts/generate-dissected-dfd.py`.", ""]
    (OUT/f"{STEM}-notes.md").write_text("\n".join(lines), encoding="utf-8")
    print(json.dumps({"drawio": str(target), "pages": 7, "unique_processes": len(process_numbers),
                      "mapped_use_cases": len(USE_CASE_MAPPING), "data_flows": len(overview.edges),
                      "external_entities": sorted(entities), "preview_size": [overview.w, overview.h]}, indent=2))


if __name__ == "__main__":
    main()
