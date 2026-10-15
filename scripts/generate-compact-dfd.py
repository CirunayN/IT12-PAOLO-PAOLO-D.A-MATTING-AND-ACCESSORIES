"""Produce one compact Level 0 page with exactly the 15 supplied use cases.

All earlier external entities and logical stores remain. Original documents
and application data are preserved. Labor/service flows remain conceptual.
"""
from __future__ import annotations

import copy
import csv
import importlib.util
import json
from pathlib import Path
import sys
import xml.etree.ElementTree as ET

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "dfd"
STEM = "Level-0-DFD-Compact"
spec = importlib.util.spec_from_file_location("dissected_dfd", Path(__file__).with_name("generate-dissected-dfd.py"))
support = importlib.util.module_from_spec(spec)
spec.loader.exec_module(support)
classic = support.classic
actor, store, op = support.actor, support.store, support.op
OWNER, CASHIER, CUSTOMER, LABOR = support.OWNER, support.CASHIER, support.CUSTOMER, support.LABOR

STORE_TITLES = {"D1": "Product\nRecords", "D2": "Product Sale\nRecords", "D3": "Service Sale\nRecords*",
                "D4": "User Account\nRecords", "D5": "Database\nBackup Files", "D6": "System\nDatabase"}
MODULES = [
    ("accounts", "Accounts and Access", [
        op(1, "Log In", "", [actor(OWNER, "Username and password", "Admin login result"),
                              actor(CASHIER, "Username and password", "Cashier login result")],
           [store("D4", "Credentials, role, access", "Authenticated session")]),
        op(2, "Create User\nAccount", "", [actor(OWNER, "New user/admin password", "Created user account")],
           [store("D4", "Existing users/admin data", "New account and role")]),
        op(3, "Enable or Disable\nUser Account", "", [actor(OWNER, "User ID and access choice", "Updated access status")],
           [store("D4", "Current account status", "New status/session data")]),
    ]),
    ("inventory", "Inventory", [
        op(4, "Add Product", "", [actor(OWNER, "New product details", "Saved product details")],
           [store("D1", "Product/category data", "New product record")]),
        op(5, "Update Product", "", [actor(OWNER, "Product ID and changes", "Updated product details")],
           [store("D1", "Existing product data", "Changed product/threshold")]),
        op(6, "View Inventory", "", [actor(OWNER, "Inventory filters and sort", "Filtered stock list")],
           [store("D1", "Products, prices, stock")]),
        op(7, "Record Incoming\nStock", "", [actor(OWNER, "Product, quantity,\nprice and batch details", "Receiving confirmation")],
           [store("D1", "Product/current stock", "Received stock batch")]),
    ]),
    ("sales", "Sales", [
        op(8, "Process Sales\nTransaction", "", [
            actor(OWNER, "Items, quantity, payment", "Completed transaction"),
            actor(CASHIER, "Items, quantity, payment", "Completed transaction"),
            actor(CUSTOMER, "Order/payment details", "Purchase confirmation"),
            actor(LABOR, "Service status*", "Service job details*"),
        ], [store("D1", "Available items/prices", "Deducted stock balances"),
            store("D2", None, "Recorded sale/payment"),
            store("D3", None, "Service charge/status*")]),
        op(9, "View Sales History", "", [
            actor(OWNER, "Sales-history filters", "Sales-history records"),
            actor(CASHIER, "Own-sales filters", "Own transaction records"),
        ], [store("D2", "Authorized sale records")]),
        op(10, "Print Sales Receipt", "", [
            actor(OWNER, "Sale ID/print request", "Printable receipt"),
            actor(CASHIER, "Sale ID/print request", "Printable receipt"),
            actor(CUSTOMER, None, "Purchase receipt"),
        ], [store("D2", "Sale, items, payment,\ntotal and change")]),
    ]),
    ("reports", "Reports", [
        op(11, "Generate Sales\nReport", "", [actor(OWNER, "Sales-report dates/filters", "Sales summary")],
           [store("D2", "Period sales data"), store("D3", "Period service data*")]),
        op(12, "Generate Inventory\nReport", "", [actor(OWNER, "Inventory-report filters", "Inventory summary")],
           [store("D1", "Stock/threshold data")]),
        op(13, "Print or Download\nReports", "", [actor(OWNER, "Report/output selection", "Printed/downloaded report")]),
    ]),
    ("backup", "Database Backup and Restore", [
        op(14, "Create Database\nBackup", "", [actor(OWNER, "Backup request", "Saved backup details")],
           [store("D6", "Current database data"), store("D5", None, "New database backup")]),
        op(15, "Restore Database\nBackup", "", [actor(OWNER, "Backup/admin password", "Restoration result")],
           [store("D5", "Selected backup data"), store("D4", "Admin credentials/role"),
            store("D6", None, "Restored database data")]),
    ]),
]


def textparts(n):
    w, h = n["w"], n["h"]
    if n["kind"] == "process":
        return [(n["ref"], 6, 1, w-12, 27, 18, True),
                (n["title"], 7, 30, w-14, h-35, 20, True)]
    if n["kind"] == "external":
        return [(n["title"], 6, 0, w-12, h, 17, True)]
    return [(n["ref"], 0, 0, 49, h, 17, True),
            (n["title"], 58, 2, w-63, h-4, 17, True)]


classic.textparts = support.textparts = textparts


def connection(d, p, other, label, direction, label_y, pf, slot):
    is_actor = d.nodes[other]["kind"] == "external"
    read = direction == "read"
    if is_actor:
        source, target = (other, p) if read else (p, other)
        source_side, target_side = ("right", "left") if read else ("left", "right")
        sf, tf = (.25, pf) if read else (pf, .80)
        label_x, label_w = 255, 320
        outside, inside = 210+slot*6+(0 if read else 3), 585+slot*5+(0 if read else 3)
    else:
        source, target = (other, p) if read else (p, other)
        source_side, target_side = ("left", "right") if read else ("right", "left")
        sf, tf = (.25, pf) if read else (pf, .80)
        label_x, label_w = 965, 310
        outside, inside = 1295-slot*6-(0 if read else 3), 940+slot*5+(0 if read else 3)
    a, b = d.point(source, source_side, sf), d.point(target, target_side, tf)
    line_y = label_y + (len(label.splitlines())*19+10)/2
    if read:
        via = [(outside, a[1]), (outside, line_y), (inside, line_y), (inside, b[1])]
    else:
        via = [(inside, a[1]), (inside, line_y), (outside, line_y), (outside, b[1])]
    classic.flow(d, source, target, label, source_side, target_side, sf, tf, via,
                 (label_x, label_y), label_w, 14)


def link(d, source, target, label, bypass=False):
    a, b = d.point(source, "bottom", .50 if not bypass else .80), d.point(target, "top", .50 if not bypass else .80)
    label_y = d.nodes[target]["row_start"]-18 if not bypass else d.nodes[target]["row_start"]-58
    if bypass:
        route_y = d.nodes[source]["row_end"]+12
        via = [(a[0], route_y), (1535, route_y), (1535, label_y+12), (b[0], label_y+12)]
    else:
        via = [(a[0], label_y+12), (b[0], label_y+12)]
    classic.flow(d, source, target, label, "bottom", "top", .80 if bypass else .50,
                 .80 if bypass else .50, via, (610, label_y), 300, 13)


def module(key, name, operations):
    d = classic.Diagram(f"compact-{key}", name, name, "", 1550, 1000, [])
    y = 80
    for item in operations:
        slots = max(len(item["actors"]), len(item["stores"]), 1)
        height = 70+slots*90
        p = item["id"]
        classic.process(d, p, 610, y+(height-105)/2, item["title"], "", 300, 105)
        d.nodes[p]["row_start"], d.nodes[p]["row_end"] = y, y+height
        for slot, (name, incoming, outgoing) in enumerate(item["actors"]):
            ident = f"{key}-{p}-actor-{slot}"
            classic.external(d, ident, 20, y+40+slot*90, name, 170, 48)
            pf = .10 + slot*.65/max(len(item["actors"])-1, 1)
            if incoming:
                connection(d, p, ident, incoming, "read", y+16+slot*90, pf, slot)
            if outgoing:
                connection(d, p, ident, outgoing, "write", y+70+slot*90, min(.95, pf+.15), slot)
        for slot, (ref, incoming, outgoing) in enumerate(item["stores"]):
            ident = f"{key}-{p}-store-{ref}-{slot}"
            classic.store(d, ident, ref, 1320, y+35+slot*90, STORE_TITLES[ref], "", 190, 62)
            pf = .10 + slot*.65/max(len(item["stores"])-1, 1)
            if incoming:
                connection(d, p, ident, incoming, "read", y+16+slot*90, pf, slot)
            if outgoing:
                connection(d, p, ident, outgoing, "write", y+70+slot*90, min(.95, pf+.15), slot)
        y += height+40
    d.h = y+50
    if key == "sales":
        link(d, "8.0", "10.0", "Completed sale ID", True)
        link(d, "9.0", "10.0", "Selected sale ID")
    if key == "reports":
        link(d, "11.0", "13.0", "Sales report data", True)
        link(d, "12.0", "13.0", "Inventory report data")
    classic.validate([d])
    return d


def overview(modules):
    lookup = {d.id.removeprefix("compact-"): d for d in modules}
    d = classic.Diagram("compact-level-0", "Level 0 - 15 processes (single page)",
                        "Figure 3. Level 0 Data Flow Diagram", "", 3190, 1000, [])
    d.headings = []
    bottom = 0
    for col, names in enumerate([["inventory", "sales"], ["accounts", "reports", "backup"]]):
        x_offset, y_offset = 30+col*1580, 120
        for name in names:
            source_module = lookup[name]
            mapping = {ident: f"{name}-{ident}" for ident in source_module.nodes}
            d.headings.append((source_module.title, x_offset, y_offset, 1550, 50))
            for ident, n in source_module.nodes.items():
                translated = copy.deepcopy(n)
                translated["id"] = mapping[ident]
                translated["x"] += x_offset
                translated["y"] += y_offset
                d.nodes[translated["id"]] = translated
            for edge in source_module.edges:
                translated = copy.deepcopy(edge)
                translated["id"] = "overview-"+edge["id"]
                translated["source"], translated["target"] = mapping[edge["source"]], mapping[edge["target"]]
                translated["points"] = [(x+x_offset, y+y_offset) for x, y in edge["points"]]
                translated["lx"] += x_offset
                translated["ly"] += y_offset
                d.edges.append(translated)
            y_offset += source_module.h+15
        bottom = max(bottom, y_offset)
    d.h = bottom+155
    classic.validate([d])
    return d


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    modules = [module(key, name, operations) for key, name, operations in MODULES]
    d = overview(modules)
    processes = {n["ref"] for n in d.nodes.values() if n["kind"] == "process"}
    assert processes == {f"{i}.0" for i in range(1, 16)}
    assert {n["title"] for n in d.nodes.values() if n["kind"] == "external"} == {OWNER, CASHIER, CUSTOMER, LABOR}
    assert {n["ref"] for n in d.nodes.values() if n["kind"] == "store"} == set(STORE_TITLES)
    support.LEGEND = "Rectangle = external entity | Numbered box = process | D1-D6 = logical data stores | Arrow = data flow"
    support.NOTE = "Repeated labels refer to the same entity/store. Cashier history includes only their own transactions."
    support.SERVICE_NOTE = "*Labor/service business flows are retained from the prior DFD; they are not covered by the supplied use case."
    mxfile = ET.Element("mxfile", host="app.diagrams.net", type="device", pages="1", compressed="false")
    support.graph_xml(mxfile, d)
    ET.indent(mxfile)
    target = OUT/f"{STEM}.drawio"
    ET.ElementTree(mxfile).write(target, encoding="utf-8", xml_declaration=True)
    support.render(d, STEM, 1)
    with (OUT/f"{STEM}-flows.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["flow_id", "source", "destination", "data"])
        for e in d.edges:
            writer.writerow([e["id"], e["source"], e["target"], e["label"].replace("\n", " ")])
    process_list = [(item["id"], item["title"].replace("\n", " ")) for _, _, operations in MODULES for item in operations]
    rows = "\n".join(f"| {ident} | {name} |" for ident, name in process_list)
    (OUT/f"{STEM}-notes.md").write_text(
        "# Compact Level 0 — one page\n\nExactly 15 processes correspond to the supplied use-case image. "
        "Checkout calculation, payment checking, sale saving and stock deduction are within Process Sales Transaction "
        "rather than extra process boxes. There are no extra focus pages or service-process boxes.\n\n"
        "| Process | Operation |\n| --- | --- |\n"+rows+"\n\n"
        "Owner, Cashier, Customer, Labor and D1-D6 are retained. Repeated labels identify the same entity or store. "
        "D6 is the aggregate database state, not another physical database. Cashier history is limited to their "
        "own transactions. Labor and D3 service exchanges remain conceptual business flows within Sales, "
        "marked with an asterisk because they are not in the supplied use case. These are not new application functions.\n\n"
        "Earlier diagrams are preserved. The older context and Level 1 pages require matching scope and numbering "
        "revisions before inclusion with this Level 0 in the final document. No application files or database data "
        "were changed. Regenerate with `scripts/generate-compact-dfd.py`.\n", encoding="utf-8")
    print(json.dumps({"drawio": str(target), "pages": 1, "processes": len(processes), "flows": len(d.edges),
                      "canvas": [d.w, d.h]}, indent=2))


if __name__ == "__main__":
    main()
