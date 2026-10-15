"""Export the connected Graphviz layout as native editable draw.io shapes."""
from __future__ import annotations

import csv
import bisect
import importlib.util
import json
import math
from pathlib import Path
import re
import sys
import xml.etree.ElementTree as ET

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "dfd"
SPACED = "--spaced" in sys.argv
INPUT_STEM = "Level-0-DFD-Redone"
STEM = "Level-0-DFD-Readable" if SPACED else INPUT_STEM
FLOW_FONT = 26 if SPACED else 13
spec = importlib.util.spec_from_file_location("connected_support", Path(__file__).with_name("generate-compact-dfd.py"))
support = importlib.util.module_from_spec(spec)
spec.loader.exec_module(support)
classic = support.classic
renderer = support.support


def textparts(node):
    w, h = node["w"], node["h"]
    if node["kind"] == "process":
        if SPACED:
            return [(node["ref"], 10, 1, w-20, 42, 36, True),
                    (node["title"], 12, 53, w-24, h-60, 44, True)]
        return [(node["ref"], 8, 1, w-16, 26, 18, True),
                (node["title"], 8, 31, w-16, h-36, 22, True)]
    if node["kind"] == "external":
        return [(node["title"], 8, 0, w-16, h, 44 if SPACED else 22, True)]
    if SPACED:
        return [(node["ref"], 2, 0, 79, h, 36, True),
                (node["title"], 97, 0, w-109, h, 40, True)]
    return [(node["ref"], 2, 0, 49, h, 26 if SPACED else 18, True),
            (node["title"], 61, 0, w-70, h, 36 if SPACED else 20, True)]


classic.textparts = renderer.textparts = textparts


def coords(text):
    return tuple(float(value) for value in text.split(","))


def nearest_port(node, point):
    x, y, w, h = (node[k] for k in ("x", "y", "w", "h"))
    px, py = point
    distances = [(abs(px-x), "left"), (abs(px-x-w), "right"),
                 (abs(py-y), "top"), (abs(py-y-h), "bottom")]
    side = min(distances)[1]
    fraction = (py-y)/h if side in ("left", "right") else (px-x)/w
    return side, max(0, min(1, fraction))


def space_geometry(d):
    """Expand crowded routing coordinates without changing any connection.

    A strictly increasing transform on each axis preserves orthogonal paths,
    crossings and obstacle avoidance. Tight parallel routing lanes expand to
    28 pixels; existing open space expands by 35 percent.
    """
    x_values, y_values = set(), set()
    for node in d.nodes.values():
        x_values.update([node["x"], node["x"]+node["w"]])
        y_values.update([node["y"], node["y"]+node["h"]])
    for edge in d.edges:
        for x, y in edge["points"]:
            x_values.add(x)
            y_values.add(y)
    def transform(values):
        # Subpixel differences in Graphviz's rounded coordinates represent
        # the same routing lane rather than additional independent lanes.
        knots = sorted({round(value) for value in values} | {0})
        expanded = [float(knots[0])]
        for a, b in zip(knots, knots[1:]):
            expanded.append(expanded[-1]+max(28, (b-a)*1.35))
        def apply(value):
            index = max(0, min(len(knots)-2, bisect.bisect_right(knots, value)-1))
            fraction = (value-knots[index])/(knots[index+1]-knots[index])
            return expanded[index]+fraction*(expanded[index+1]-expanded[index])
        return apply
    fx, fy = transform(x_values), transform(y_values)
    for node in d.nodes.values():
        left, top = fx(node["x"]), fy(node["y"])
        right, bottom = fx(node["x"]+node["w"]), fy(node["y"]+node["h"])
        node.update(x=left, y=top, w=right-left, h=bottom-top)
    for edge in d.edges:
        preferred = fx(edge["lx"]+edge["lw"]/2), fy(edge["ly"]+edge["lh"]/2)
        edge["points"] = [(fx(x), fy(y)) for x, y in edge["points"]]
        edge["lx"], edge["ly"] = preferred[0]-edge["lw"]/2, preferred[1]-edge["lh"]/2
        for name, node_id, anchor in [("sf", edge["source"], edge["points"][0]),
                                      ("tf", edge["target"], edge["points"][-1])]:
            node = d.nodes[node_id]
            side = edge["source_side"] if name == "sf" else edge["target_side"]
            edge[name] = ((anchor[1]-node["y"])/node["h"] if side in ("left", "right")
                          else (anchor[0]-node["x"])/node["w"])
    d.w = math.ceil(fx(d.w))
    d.h = math.ceil(fy(d.h))
    d.process_header_height = 46
    d.title_size, d.note_size, d.legend_size = 60, 32, 26
    d.store_partition_width = 85
    d.stroke_width = 3


def place_labels(d):
    """Keep readable flow labels clear of all shapes and other labels."""
    node_padding, label_padding = (18, 12) if SPACED else (4, 2)
    node_boxes = [(n["x"]-node_padding, n["y"]-node_padding,
                   n["x"]+n["w"]+node_padding, n["y"]+n["h"]+node_padding)
                  for n in d.nodes.values()]
    placed = []
    def overlaps(a, b):
        return a[0] < b[2] and a[2] > b[0] and a[1] < b[3] and a[3] > b[1]
    for e in sorted(d.edges, key=lambda item: item["lw"], reverse=True):
        width, height = e["lw"], e["lh"]
        preferred = (e["lx"]+width/2, e["ly"]+height/2)
        candidates = [(preferred[0], preferred[1], 0)]
        for a, b in zip(e["points"], e["points"][1:]):
            length = math.dist(a, b)
            if length < 12:
                continue
            for fraction in [.5, .25, .75, .1, .9]:
                cx, cy = a[0]+fraction*(b[0]-a[0]), a[1]+fraction*(b[1]-a[1])
                if abs(a[1]-b[1]) < .1:
                    offsets = [-height/2-4, height/2+4, -height/2-25, height/2+25,
                               -height/2-55, height/2+55, -height/2-80, height/2+80]
                    if SPACED:
                        offsets += [sign*(height/2+distance) for distance in (120, 180, 260, 340) for sign in (-1, 1)]
                    for offset in offsets:
                        candidates.append((cx, cy+offset, abs(offset)))
                else:
                    for offset in [-width/2-4, width/2+4, -width/2-24, width/2+24]:
                        candidates.append((cx+offset, cy, 18+abs(offset)/6))
        candidates.sort(key=lambda c: c[2]+math.dist(c[:2], preferred)/12)
        for cx, cy, _ in candidates:
            box = (cx-width/2-label_padding, cy-height/2-label_padding,
                   cx+width/2+label_padding, cy+height/2+label_padding)
            if box[0] < 12 or box[2] > d.w-12 or box[1] < (110 if SPACED else 78) or box[3] > d.h-(190 if SPACED else 140):
                continue
            if any(overlaps(box, other) for other in node_boxes+placed):
                continue
            e["lx"], e["ly"] = cx-width/2, cy-height/2
            placed.append(box)
            break
        else:
            raise ValueError(f"No clear label position for {e['id']}: {e['label']}")


def export():
    data = json.loads((OUT/f"{INPUT_STEM}-layout.json").read_text(encoding="utf-8"))
    layout = data["layout"]
    x0, y0, x1, y1 = coords(layout["bb"])
    margin, header = 50, 110
    def point(p):
        return (p[0]-x0+margin, y1-p[1]+header)
    d = classic.Diagram("level-0-readable" if SPACED else "level-0-redone",
                        "Level 0 - spaced readable layout" if SPACED else "Level 0 - connected processes",
                        "Figure 3. Level 0 Data Flow Diagram", "",
                        math.ceil(x1-x0+2*margin), math.ceil(y1-y0+header+150), [])
    descriptions = {node["id"]: node for node in data["nodes"]}
    graph_nodes = {node["name"]: node for node in layout["objects"]}
    node_gvid = {node["_gvid"]: node["name"] for node in layout["objects"]}
    for ident, n in descriptions.items():
        cx, cy = point(coords(graph_nodes[ident]["pos"]))
        d.node(ident, n["kind"], cx-n["width"]/2, cy-n["height"]/2,
               n["width"], n["height"], n["title"], "", n["ref"] or ident)
    descriptions_by_edge = {flow["id"]: flow for flow in data["flows"]}
    for edge in layout["edges"]:
        expected = descriptions_by_edge[edge["id"]]
        source, target = node_gvid[edge["tail"]], node_gvid[edge["head"]]
        assert (source, target) == (expected["source"], expected["target"])
        line = next(operation for operation in edge["_draw_"] if operation["op"] in ("b", "L"))
        points = []
        for p in line["points"]:
            converted = point(p)
            if not points or math.dist(converted, points[-1]) > .05:
                points.append(converted)
        tip = re.search(r"(?:^|\s)e,([^ ]+)", edge["pos"])
        assert tip is not None
        tip_point = point(coords(tip.group(1)))
        source_side, sf = nearest_port(d.nodes[source], points[0])
        target_side, tf = nearest_port(d.nodes[target], tip_point)
        points[0] = d.point(source, source_side, sf)
        if target_side in ("left", "right"):
            points[-1] = (points[-1][0], d.point(target, target_side, tf)[1])
        else:
            points[-1] = (d.point(target, target_side, tf)[0], points[-1][1])
        points.append(d.point(target, target_side, tf))
        # Graphviz's rounded textual coordinates may differ by tiny fractions.
        # Snap aligned controls to make native draw.io paths exactly orthogonal.
        for index in range(1, len(points)):
            a, b = points[index-1], points[index]
            if abs(a[0]-b[0]) < .7:
                points[index] = (a[0], b[1])
            elif abs(a[1]-b[1]) < .7:
                points[index] = (b[0], a[1])
        compact = []
        for p in points:
            if not compact or math.dist(p, compact[-1]) > .05:
                compact.append(p)
        assert "xlp" in edge, f"Missing positioned label for {edge['id']}"
        cx, cy = point(coords(edge["xlp"]))
        label = expected["label"]
        width = max(classic.font(FLOW_FONT).getlength(label)+(16 if SPACED else 8), 30)
        label_height = 36 if SPACED else 19
        d.edges.append(dict(id=edge["id"], source=source, target=target, label=label,
                            source_side=source_side, target_side=target_side, sf=sf, tf=tf,
                            points=compact, lx=cx-width/2, ly=cy-label_height/2,
                            lw=width, lh=label_height, size=FLOW_FONT))

    if SPACED:
        space_geometry(d)
    place_labels(d)
    classic.validate([d])

    # Verify that this is a connected DFD, not a collection of disconnected rows.
    adjacency = {ident: set() for ident in d.nodes}
    for e in d.edges:
        assert "process" in (d.nodes[e["source"]]["kind"], d.nodes[e["target"]]["kind"])
        adjacency[e["source"]].add(e["target"])
        adjacency[e["target"]].add(e["source"])
    visited, pending = set(), ["owner"]
    while pending:
        ident = pending.pop()
        if ident in visited:
            continue
        visited.add(ident)
        pending.extend(adjacency[ident]-visited)
    assert visited == set(d.nodes)
    for n in d.nodes.values():
        if n["kind"] == "process":
            assert any(e["target"] == n["id"] for e in d.edges)
            assert any(e["source"] == n["id"] for e in d.edges)
        for value, _, _, width, height, size, bold in textparts(n):
            assert all(classic.font(size, bold).getlength(line) <= width for line in value.splitlines()), value
            assert len(value.splitlines())*(size+5) <= height+2, value
    assert len([n for n in d.nodes.values() if n["kind"] == "process"]) == 12
    assert len([n for n in d.nodes.values() if n["kind"] == "external"]) == 4
    assert len([n for n in d.nodes.values() if n["kind"] == "store"]) == 6

    renderer.LEGEND = "Rectangle = external entity | Numbered box = process | D1-D6 = logical data stores | Arrow = data flow"
    renderer.NOTE = "Cashier sales history is restricted to their own transactions. D6 represents the aggregate database records."
    renderer.SERVICE_NOTE = "*Labor/service business flows are retained from the original Level 0 and are outside the supplied use case."
    mxfile = ET.Element("mxfile", host="app.diagrams.net", type="device", pages="1", compressed="false")
    renderer.graph_xml(mxfile, d)
    ET.indent(mxfile)
    target = OUT/f"{STEM}.drawio"
    ET.ElementTree(mxfile).write(target, encoding="utf-8", xml_declaration=True)
    renderer.render(d, STEM, 1 if SPACED else 1.5)
    with (OUT/f"{STEM}-flows.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["flow_id", "source", "destination", "data"])
        writer.writerows((e["id"], e["source"], e["target"], e["label"]) for e in d.edges)
    mapping = [
        ("Add Product; Update Product", "1.0 Manage Product Records"),
        ("Record Incoming Stock", "2.0 Record Incoming Stock"),
        ("View Inventory", "3.0 View Inventory"),
        ("Process Sales Transaction", "4.0 Process Sales Transaction"),
        ("View Sales History", "5.0 View Sales History"),
        ("Print Sales Receipt", "6.0 Print Sales Receipt"),
        ("Generate Sales Report; Print or Download Reports", "7.0 Generate Sales Report"),
        ("Generate Inventory Report; Print or Download Reports", "8.0 Generate Inventory Report"),
        ("Log In", "9.0 Log In"),
        ("Create User Account; Enable or Disable User Account", "10.0 Manage User Accounts"),
        ("Create Database Backup", "11.0 Create Database Backup"),
        ("Restore Database Backup", "12.0 Restore Database Backup"),
    ]
    with (OUT/f"{STEM}-use-cases.csv").open("w", encoding="utf-8-sig", newline="") as stream:
        writer = csv.writer(stream)
        writer.writerow(["supplied_use_case", "level_0_process"])
        writer.writerows(mapping)
    if SPACED:
        baseline = list(csv.DictReader((OUT/f"{INPUT_STEM}-flows.csv").open(encoding="utf-8-sig")))
        actual = list(csv.DictReader((OUT/f"{STEM}-flows.csv").open(encoding="utf-8-sig")))
        assert actual == baseline, "Spacing must preserve every data flow exactly."
    (OUT/f"{STEM}-notes.md").write_text(
        ("# Readable Level 0: spacing-only revision\n\n"
         "The 12 processes, four entities, six stores and 55 named connections are identical to the accepted "
         "Redone file. Only positions, spacing, font sizes and line styles are changed. Tight parallel routing "
         "lanes are spread apart, and labels have larger text and padding.\n\n" if SPACED else
         "# Redone Level 0: one connected page\n\n")+
        "This revision has 12 process boxes, four external entities and six shared logical stores. "
        "Each entity/store appears once. All nodes belong to the same connected data-flow network. "
        "Inventory, Sales and Reports are separated into three, three and two distinct operations; "
        "Accounts and Backup/Restore each have two operations.\n\n"
        "Add/update product details are one Maintain Product Records transformation, and account creation/access "
        "changes are one Manage User Accounts transformation. Sales and inventory reports include the requested "
        "print/download output. This reduces process counts while preserving the supplied use-case capabilities. "
        "Each process has at least one input and one output. All arrows carry named data; confirmations that "
        "are not essential to the overview are omitted.\n\n"
        "Owner is labelled Admin/Owner; Cashier, Customer and Labor are retained. The original product, product-sale "
        "and service-sale stores remain. D4 contains accounts/sessions, D5 contains backups, and D6 is an aggregate "
        "database state, not another physical database. Cashier history is restricted to their own sales.\n\n"
        "Marked Labor/service flows retain the earlier conceptual business workflow and are outside the supplied "
        "use case. They are not claims of implemented Laravel service functions. No application source or database "
        "records were modified. Previous revisions are preserved. Earlier context and detailed pages need "
        "corresponding scope/numbering changes before use together in the final document.\n\n"+
        ("Regenerate with `scripts/render-connected-dfd.py --spaced` using the accepted Redone layout data.\n"
         if SPACED else "Regenerate using `scripts/layout-connected-dfd.mjs` then `scripts/render-connected-dfd.py`.\n"), encoding="utf-8")
    print(json.dumps({"drawio": str(target), "pages": 1, "processes": 12,
                      "unique_entities": 4, "shared_stores": 6, "flows": len(d.edges),
                      "canvas": [d.w,d.h]}, indent=2))


if __name__ == "__main__":
    export()
