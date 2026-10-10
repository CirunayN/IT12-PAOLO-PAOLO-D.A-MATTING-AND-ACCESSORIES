"""Generate schema-only ERD artifacts from the supplied application snapshot.

Column membership comes from the JSON; SQL Server types and constraints come
from the matching repository migrations. No record values leave this script.
Requires reportlab and pypdf; use Poppler separately to render the PDF preview.
"""

from __future__ import annotations

import argparse
import csv
from datetime import datetime, timedelta, timezone
import hashlib
import html
import json
from pathlib import Path

from reportlab.lib.colors import HexColor
from reportlab.lib.pagesizes import A3, landscape
from reportlab.pdfbase.pdfmetrics import stringWidth
from reportlab.pdfgen import canvas


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "erd"
PDF = ROOT / "output" / "pdf" / "updated-erd-2026-10-10.pdf"
W, H = landscape(A3)
INK = "#162235"
MUTED = "#59697c"
LINE = "#64748b"
RED = "#b91c1c"
BLUE = "#1d4ed8"
PURPLE = "#6d28d9"
GRAY = "#475569"
ROW_H = 12
HEADER_H = 27
LABEL_H = 17


def col(name, typ="nvarchar(255)", nullable=False, keys="", note=""):
    return dict(name=name, type=typ, nullable=nullable, keys=keys.split(",") if keys else [], note=note)


def pk(name="id", typ="bigint", identity=True):
    return col(name, typ, keys="PK", note="identity" if identity else "")


def fk(name, nullable=False, unique=False):
    return col(name, "bigint", nullable, "FK,UK" if unique else "FK")


def timestamps():
    return [col("created_at", "datetime", True), col("updated_at", "datetime", True)]


# Explicit, reviewed migration-derived definitions, restricted to backup columns.
TABLES = {
    "users": [pk(), col("name"), col("username", nullable=True, keys="UK"), col("email", keys="UK"),
              col("role", note="default Admin"), col("is_active", "bit", note="default 1"),
              col("email_verified_at", "datetime", True), col("password"),
              col("remember_token", "nvarchar(100)", True), *timestamps()],
    "tbl_Category": [pk("ID"), col("Name"), col("Is_Archived", "bit", note="default 0"), *timestamps()],
    "tbl_Status": [pk("ID"), col("Name"), *timestamps()],
    "tbl_Payment_Method": [pk("ID"), col("Name"), *timestamps()],
    "tbl_Product": [pk("ID"), fk("Category_ID"), fk("Status_ID"), col("Name"),
                    col("Description", "nvarchar(max)", True),
                    col("Images", "nvarchar(max)", True, note="JSON image paths"),
                    col("Image", nullable=True, note="single image path"), *timestamps()],
    "tbl_Stock_in": [pk("ID"), fk("Product_ID"), fk("User_ID", True),
                     col("Quantity", "decimal(10,2)", note="default 0"),
                     col("Remaining_Quantity", "decimal(10,2)", note="default 0; FIFO balance"),
                     col("Cost_Price", "decimal(10,2)", note="default 0"),
                     col("Retail_Price", "decimal(10,2)", note="default 0"),
                     col("Has_Expiration", "bit", note="default 0"),
                     col("Expiration_Date", "date", True), col("Condition", "nvarchar(50)", note="default Good"),
                     *timestamps()],
    "tbl_Sale": [pk("ID"), fk("User_ID"), fk("Payment_Method_ID"), col("Date", "datetime"),
                 col("Total", "decimal(10,2)", note="default 0"),
                 col("Amount_Received", "decimal(10,2)", note="default 0"),
                 col("Change_Amount", "decimal(10,2)", note="default 0"),
                 col("GCash_Reference_Number", "nvarchar(100)", True), *timestamps()],
    "tbl_Sold_Item": [pk("ID"), fk("Product_ID"), fk("Sale_ID"), col("Quantity", "decimal(10,2)", note="default 1"),
                      col("Total", "decimal(10,2)", note="default 0"), *timestamps()],
    "admin_recovery_codes": [pk(), fk("user_id"), col("code_hash"), col("used_at", "datetime", True), *timestamps()],
    "email_change_codes": [pk(), fk("user_id", unique=True), col("new_email"), col("code_hash"),
                           col("expires_at", "datetime"), *timestamps()],
    "password_reset_requests": [pk(), fk("user_id"), fk("approved_by", True), col("code_hash", nullable=True),
                                col("approved_at", "datetime", True), col("expires_at", "datetime", True),
                                col("used_at", "datetime", True), col("completed_at", "datetime", True), *timestamps()],
    "pending_employee_registrations": [pk(), fk("created_by"), col("name"), col("username", keys="UK"),
                                       col("email", keys="UK"), col("password_hash"), col("role", note="default Employee"),
                                       col("code_hash"), col("expires_at", "datetime"), *timestamps()],
    "password_reset_codes": [pk(), col("email", note="application reference to users.email"),
                             col("code_hash"), col("expires_at", "datetime"), *timestamps()],
    "password_reset_tokens": [pk("email", "nvarchar(255)", False), col("token"), col("created_at", "datetime", True)],
    "sessions": [pk("id", "nvarchar(255)", False), col("user_id", "bigint", True, note="indexed; application reference only"),
                 col("ip_address", "nvarchar(45)", True), col("user_agent", "nvarchar(max)", True),
                 col("payload", "nvarchar(max)"), col("last_activity", "int", note="Unix timestamp")],
    "cache": [pk("key", "nvarchar(255)", False), col("value", "nvarchar(max)"), col("expiration", "bigint", note="Unix timestamp")],
    "cache_locks": [pk("key", "nvarchar(255)", False), col("owner"), col("expiration", "bigint", note="Unix timestamp")],
    "jobs": [pk(), col("queue"), col("payload", "nvarchar(max)"), col("attempts", "smallint"),
             col("reserved_at", "int", True), col("available_at", "int"), col("created_at", "int")],
    "job_batches": [pk("id", "nvarchar(255)", False), col("name"), col("total_jobs", "int"),
                    col("pending_jobs", "int"), col("failed_jobs", "int"), col("failed_job_ids", "nvarchar(max)"),
                    col("options", "nvarchar(max)", True), col("cancelled_at", "int", True),
                    col("created_at", "int"), col("finished_at", "int", True)],
    "failed_jobs": [pk(), col("uuid", keys="UK"), col("connection"), col("queue"), col("payload", "nvarchar(max)"),
                    col("exception", "nvarchar(max)"), col("failed_at", "datetime", note="default CURRENT_TIMESTAMP")],
    "migrations": [pk(typ="int"), col("migration"), col("batch", "int")],
}


def rel(parent, child, child_column, parent_column="ID", optional=False, unique=False, logical=False, delete="CASCADE"):
    return dict(parent=parent, parent_column=parent_column, child=child, child_column=child_column,
                optional_parent=optional, unique_child=unique, logical=logical,
                on_delete=None if logical else delete)


RELS = [
    rel("tbl_Category", "tbl_Product", "Category_ID"),
    rel("tbl_Status", "tbl_Product", "Status_ID"),
    rel("tbl_Product", "tbl_Stock_in", "Product_ID"),
    rel("users", "tbl_Stock_in", "User_ID", "id", optional=True, delete="SET NULL"),
    rel("users", "tbl_Sale", "User_ID", "id"),
    rel("tbl_Payment_Method", "tbl_Sale", "Payment_Method_ID"),
    rel("tbl_Product", "tbl_Sold_Item", "Product_ID"),
    rel("tbl_Sale", "tbl_Sold_Item", "Sale_ID"),
    rel("users", "admin_recovery_codes", "user_id", "id"),
    rel("users", "email_change_codes", "user_id", "id", unique=True),
    rel("users", "pending_employee_registrations", "created_by", "id"),
    rel("users", "password_reset_requests", "user_id", "id"),
    rel("users", "password_reset_requests", "approved_by", "id", optional=True, delete="NO ACTION"),
    rel("users", "sessions", "user_id", "id", optional=True, logical=True),
    rel("users", "password_reset_codes", "email", "email", logical=True),
    rel("users", "password_reset_tokens", "email", "email", unique=True, logical=True),
]

BUSINESS = ["users", "tbl_Category", "tbl_Status", "tbl_Payment_Method", "tbl_Product", "tbl_Stock_in", "tbl_Sale", "tbl_Sold_Item"]
SECURITY = ["users", "admin_recovery_codes", "email_change_codes", "pending_employee_registrations",
            "password_reset_requests", "sessions", "password_reset_codes", "password_reset_tokens"]
INFRA = ["jobs", "job_batches", "failed_jobs", "cache", "cache_locks", "migrations"]


class Scene:
    """Draw identical vector geometry to PDF and standalone SVG."""
    def __init__(self, pdf):
        self.pdf = pdf
        self.parts = []

    def rect(self, x, y, w, h, fill="#ffffff", stroke=None, radius=0):
        self.pdf.setFillColor(HexColor(fill))
        self.pdf.setStrokeColor(HexColor(stroke or fill))
        self.pdf.setLineWidth(.7)
        self.pdf.roundRect(x, y, w, h, radius, fill=1, stroke=int(bool(stroke)))
        self.parts.append(f'<rect x="{x}" y="{H-y-h}" width="{w}" height="{h}" rx="{radius}" fill="{fill}" stroke="{stroke or "none"}" stroke-width="0.7"/>')

    def text(self, x, y, value, size=9, color=INK, bold=False, align="left"):
        font = "Helvetica-Bold" if bold else "Helvetica"
        self.pdf.setFont(font, size)
        self.pdf.setFillColor(HexColor(color))
        if align == "right":
            self.pdf.drawRightString(x, y, value)
        else:
            self.pdf.drawString(x, y, value)
        anchor = "end" if align == "right" else "start"
        self.parts.append(f'<text x="{x}" y="{H-y}" font-family="Arial, Helvetica, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}" text-anchor="{anchor}" fill="{color}">{html.escape(value)}</text>')

    def path(self, points, color=LINE, width=1.1, dashed=False):
        self.pdf.setStrokeColor(HexColor(color))
        self.pdf.setLineWidth(width)
        self.pdf.setDash([4, 3] if dashed else [])
        p = self.pdf.beginPath()
        p.moveTo(*points[0])
        for pt in points[1:]:
            p.lineTo(*pt)
        self.pdf.drawPath(p)
        self.pdf.setDash([])
        d = "M " + " L ".join(f"{x} {H-y}" for x, y in points)
        dash = ' stroke-dasharray="4 3"' if dashed else ""
        self.parts.append(f'<path d="{d}" fill="none" stroke="{color}" stroke-width="{width}"{dash}/>')

    def circle(self, x, y, r, fill="#ffffff", stroke=LINE):
        self.pdf.setFillColor(HexColor(fill))
        self.pdf.setStrokeColor(HexColor(stroke))
        self.pdf.setLineWidth(1.1)
        self.pdf.circle(x, y, r, fill=1, stroke=1)
        self.parts.append(f'<circle cx="{x}" cy="{H-y}" r="{r}" fill="{fill}" stroke="{stroke}" stroke-width="1.1"/>')

    def save(self, filename, title):
        content = f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" role="img"><title>{html.escape(title)}</title>' + "".join(self.parts) + "</svg>\n"
        (OUT / filename).write_text(content, encoding="utf-8")


def entity_height(name):
    return HEADER_H + LABEL_H + len(TABLES[name]) * ROW_H + 8


def row_y(box, name):
    table, x, top, width = box
    idx = next(i for i, c in enumerate(TABLES[table]) if c["name"] == name)
    return top - HEADER_H - LABEL_H - ROW_H * idx - ROW_H / 2


def port(box, column, side="right"):
    return (box[1] + (box[3] if side == "right" else 0), row_y(box, column))


def entity(s, box, counts, theme):
    name, x, top, width = box
    height = entity_height(name)
    s.rect(x, top-height, width, height, stroke="#cbd5e1", radius=5)
    s.rect(x, top-HEADER_H, width, HEADER_H, fill=theme, radius=5)
    s.rect(x, top-HEADER_H, width, 5, fill=theme)
    s.text(x+10, top-18, name, 10.3, "#ffffff", True)
    s.text(x+width-9, top-17, f'{counts[name]} rows', 7.2, "#ffffff", align="right")
    s.rect(x, top-HEADER_H-LABEL_H, width, LABEL_H, fill="#eaf0f6")
    type_x = x + width - 95
    s.text(x+9, top-HEADER_H-12, "KEY", 6.9, MUTED, True)
    s.text(x+44, top-HEADER_H-12, "COLUMN", 6.9, MUTED, True)
    s.text(type_x, top-HEADER_H-12, "SQL SERVER TYPE", 6.6, MUTED, True)
    s.text(x+width-8, top-HEADER_H-12, "?", 7, MUTED, True, "right")
    for i, c in enumerate(TABLES[name]):
        y = row_y(box, c["name"])
        if i % 2 == 1:
            s.rect(x+.5, y-ROW_H/2, width-1, ROW_H, fill="#f7f9fb")
        key = "/".join(c["keys"])
        s.text(x+9, y-2.7, key, 6.4, theme, True)
        size = min(8.0, 7.6 if width < 290 else 8.0)
        if stringWidth(c["name"], "Helvetica-Bold" if key else "Helvetica", size) > type_x-(x+44)-5:
            size = 6.6
        s.text(x+44, y-2.7, c["name"], size, INK, bool(key))
        s.text(type_x, y-2.7, c["type"], 7.2, MUTED)
        s.text(x+width-8, y-2.7, "Y" if c["nullable"] else "-", 7.1, MUTED, align="right")


def marker(s, anchor, next_point, cardinality, color=LINE):
    """Crow's foot marker, where the outward vector points away from the table."""
    dx, dy = next_point[0]-anchor[0], next_point[1]-anchor[1]
    length = (dx*dx + dy*dy)**.5
    dx, dy = dx/length, dy/length
    px, py = -dy, dx

    def at(distance, offset=0):
        return anchor[0]+dx*distance+px*offset, anchor[1]+dy*distance+py*offset

    if cardinality == "many":
        s.path([at(0, -5), at(9), at(0, 5)], color)
        s.circle(*at(15), 3, stroke=color)
    else:
        s.path([at(5, -5), at(5, 5)], color)
        if cardinality == "optional":
            s.circle(*at(13), 3, stroke=color)
        else:
            s.path([at(11, -5), at(11, 5)], color)


def edge(s, relationship, points, caption=None):
    color = PURPLE if relationship["logical"] else LINE
    # White separation at line crossings keeps unrelated links distinct.
    s.path(points, "#f4f7fa", width=4)
    s.path(points, color, dashed=relationship["logical"])
    marker(s, points[0], points[1], "optional" if relationship["optional_parent"] else "one", color)
    marker(s, points[-1], points[-2], "optional" if relationship["unique_child"] else "many", color)
    if caption:
        x, y, text = caption
        width = stringWidth(text, "Helvetica", 7.5)+10
        s.rect(x-5, y-3, width, 13, fill="#f4f7fa", radius=2)
        s.text(x, y, text, 7.5, color)


def header(s, page, title, subtitle, created, column_count):
    s.rect(0, 0, W, H, fill="#f4f7fa")
    s.rect(40, H-56, 33, 4, fill=RED)
    s.text(40, H-77, "PAOLO PAOLO  /  DATABASE SCHEMA", 9, MUTED, True)
    s.text(40, H-113, title, 28, INK, True)
    s.text(40, H-134, subtitle, 10, MUTED)
    s.text(W-40, H-77, f'{page} / 3', 10, MUTED, True, "right")
    s.text(W-40, H-113, f'21 tables  |  {column_count} columns', 10, MUTED, align="right")
    s.text(W-40, H-134, "Snapshot: "+created, 8.8, MUTED, align="right")


def footer(s, notes):
    for i, note in enumerate(notes):
        s.text(40, 70-i*13, note, 8.2, MUTED)
    s.path([(40, 30), (W-40, 30)], "#d4dce6", .7)
    s.text(40, 15, "PK = primary key   FK = declared foreign key   UK = unique key   ? / Y = nullable column", 7.2, MUTED)
    s.text(W-40, 15, "Columns: JSON backup  |  Types / keys: 19 matching migrations (no DDL in snapshot)", 7.2, MUTED, align="right")


def business(s, counts):
    boxes = {
        "tbl_Category": ("tbl_Category", 40, 660, 245),
        "tbl_Status": ("tbl_Status", 40, 490, 245),
        "users": ("users", 40, 300, 245),
        "tbl_Product": ("tbl_Product", 417, 660, 305),
        "tbl_Stock_in": ("tbl_Stock_in", 875, 660, 275),
        "tbl_Sold_Item": ("tbl_Sold_Item", 875, 350, 275),
        "tbl_Payment_Method": ("tbl_Payment_Method", 417, 438, 305),
        "tbl_Sale": ("tbl_Sale", 417, 305, 305),
    }
    c, st, u, p, si, sold, pay, sale = (boxes[n] for n in ["tbl_Category", "tbl_Status", "users", "tbl_Product", "tbl_Stock_in", "tbl_Sold_Item", "tbl_Payment_Method", "tbl_Sale"])
    edges = [
        ([port(c,"ID"), (347,row_y(c,"ID")), (347,row_y(p,"Category_ID")), port(p,"Category_ID","left")], (301,639,"belongs to")),
        ([port(st,"ID"), (369,row_y(st,"ID")), (369,row_y(p,"Status_ID")), port(p,"Status_ID","left")], (298,429,"has status")),
        ([port(p,"ID"), (789,row_y(p,"ID")), (789,row_y(si,"Product_ID")), port(si,"Product_ID","left")], (749,630,"received as")),
        ([port(u,"id","left"), (22,row_y(u,"id")), (22,94), (1170,94), (1170,row_y(si,"User_ID")), port(si,"User_ID")], (835,99,"Processed by: User_ID (optional)")),
        ([port(u,"id"), (347,row_y(u,"id")), (347,row_y(sale,"User_ID")), port(sale,"User_ID","left")], (297,262,"cashier")),
        ([port(pay,"ID"), (750,row_y(pay,"ID")), (750,row_y(sale,"Payment_Method_ID")), port(sale,"Payment_Method_ID")], (742,320,"payment")),
        ([port(p,"ID"), (811,row_y(p,"ID")), (811,row_y(sold,"Product_ID")), port(sold,"Product_ID","left")], (790,469,"sold as")),
        ([port(sale,"ID"), (775,row_y(sale,"ID")), (775,row_y(sold,"Sale_ID")), port(sold,"Sale_ID","left")], (791,274,"sale lines")),
    ]
    for r, (points, caption) in zip(RELS[:8], edges):
        edge(s, r, points, caption)
    for name, box in boxes.items():
        entity(s, box, counts, BLUE if name == "users" else GRAY if name in ["tbl_Category","tbl_Status","tbl_Payment_Method"] else RED)
    s.text(40, 688, "CROW'S FOOT:  || exactly one     o| zero or one     o< zero or many     Solid line = migration-declared FK", 8.3, MUTED)
    footer(s, [
        "Expiration, condition and FIFO Remaining_Quantity belong to tbl_Stock_in; they are not product columns.",
        "No Sold_Item-to-Stock_in allocation FK exists. Sale lines reference products; each sale uses one payment method.",
        "Cardinality describes schema constraints. A sale can have zero stored lines at the database level; checkout applies its own rules.",
    ])


def security(s, counts):
    boxes = {
        "users": ("users", 441, 497, 301),
        "admin_recovery_codes": ("admin_recovery_codes", 40, 660, 285),
        "email_change_codes": ("email_change_codes", 40, 442, 285),
        "pending_employee_registrations": ("pending_employee_registrations", 40, 280, 285),
        "password_reset_requests": ("password_reset_requests", 861, 660, 289),
        "sessions": ("sessions", 861, 438, 289),
        "password_reset_tokens": ("password_reset_tokens", 861, 267, 289),
        "password_reset_codes": ("password_reset_codes", 441, 251, 301),
    }
    u = boxes["users"]
    configs = [
        ("admin_recovery_codes", "user_id", "id", "left", "right", 354, "recovery codes", (334,612)),
        ("email_change_codes", "user_id", "id", "left", "right", 379, "one pending email change", (335,348)),
        ("pending_employee_registrations", "created_by", "id", "left", "right", 349, "created by", (335,274)),
        ("password_reset_requests", "user_id", "id", "right", "left", 787, "requested by", (757,635)),
        ("password_reset_requests", "approved_by", "id", "right", "left", 815, "approved by (optional)", (765,588)),
        ("sessions", "user_id", "id", "right", "left", 785, "user ID (logical)", (757,349)),
        ("password_reset_codes", "email", "email", "left", "left", 405, "email (logical)", (379,304)),
        ("password_reset_tokens", "email", "email", "right", "left", 779, "email (logical)", (775,280)),
    ]
    for r, cfg in zip(RELS[8:], configs):
        child, colname, parentcol, uside, cside, middle, caption, label = cfg
        start = port(u, parentcol, uside)
        # Separate required/optional parent markers instead of stacking them at
        # one PK port. Header ports refer to the users entity as a whole.
        if child == "password_reset_requests":
            start = (u[1]+u[3], u[2]-(7 if colname == "user_id" else 20))
        end = port(boxes[child], colname, cside)
        edge(s, r, [start, (middle,start[1]), (middle,end[1]), end], (*label, caption))
    for name, box in boxes.items():
        entity(s, box, counts, BLUE if name == "users" else GRAY if name == "sessions" else PURPLE)
    s.text(40, 688, "Solid = migration-declared FK    Purple dashed = application reference, without a declared FK    o| = optional / at most one", 8.3, MUTED)
    footer(s, [
        "email_change_codes.user_id is UNIQUE: one user can have at most one pending email-change code.",
        "sessions.user_id is indexed but unconstrained. Reset emails are application references to users.email, not foreign keys.",
        "SQL Server: password_reset_requests.approved_by uses NO ACTION on delete; user_id uses CASCADE. Users appears on both diagrams.",
    ])


def infrastructure(s, counts):
    boxes = [
        ("jobs", 40, 660, 340),
        ("job_batches", 425, 660, 340),
        ("failed_jobs", 810, 660, 340),
        ("cache", 40, 412, 340),
        ("cache_locks", 425, 412, 340),
        ("migrations", 810, 412, 340),
    ]
    for box in boxes:
        entity(s, box, counts, GRAY)
    s.text(40, 688, "These six framework tables have no foreign keys to one another in the matching migrations.", 8.3, MUTED)
    s.rect(40, 137, W-80, 107, stroke="#cbd5e1", radius=5)
    s.text(56, 220, "READING THIS ERD", 9.5, INK, True)
    s.text(56, 200, "The snapshot contains table names, columns and records. It does not export SQL Server CREATE TABLE statements, indexes or FK metadata.", 9, MUTED)
    s.text(56, 182, "All 21 tables and all columns match this backup. Types, keys, nullability and delete actions were reconstructed from its 19 matching migrations.", 9, MUTED)
    s.text(56, 164, "SQL Server stores Laravel JSON/text as nvarchar(max), booleans as bit, and Laravel timestamp/dateTime columns as datetime here.", 9, MUTED)
    s.text(56, 146, "Only schema metadata and row counts appear in these artifacts. Record contents, password hashes, recovery codes and session payloads are omitted.", 9, MUTED)
    footer(s, [
        "Queue IDs / failed_job_ids are framework data, not FK columns. Similar names do not imply a database relationship.",
        "Product Image and Images store paths / JSON paths; the uploaded image files are outside the database snapshot.",
        "Backup schedules and destinations are application settings, not extra database entities in this snapshot.",
    ])


def mermaid(names, title):
    result = ["---", f"title: {title}", "---", "%% Columns from supplied JSON; types / constraints from matching SQL Server migrations.",
              "%% Solid = declared FK. Dashed = application-only reference (not an FK).", "erDiagram", "    direction LR"]
    for name in names:
        result.append(f"    {name} {{")
        for c in TABLES[name]:
            typ = c["type"].split("(")[0]
            key = ",".join(c["keys"])
            notes = [c["type"], "NULL" if c["nullable"] else "NOT NULL"]
            if c["note"]:
                notes.append(c["note"])
            result.append(f'        {typ} {c["name"]}{" "+key if key else ""} "{"; ".join(notes)}"')
        result.append("    }")
    for r in RELS:
        if r["parent"] in names and r["child"] in names:
            left = "|o" if r["optional_parent"] else "||"
            right = "o|" if r["unique_child"] else "o{"
            line = ".." if r["logical"] else "--"
            label = r["child_column"] + (" logical" if r["logical"] else "")
            result.append(f'    {r["parent"]} {left}{line}{right} {r["child"]} : "{label}"')
    return "\n".join(result) + "\n"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("snapshot", type=Path)
    args = parser.parse_args()
    snapshot = json.loads(args.snapshot.read_text(encoding="utf-8-sig"))
    assert snapshot["format"] == "paolo-paolo-database-snapshot"
    assert set(snapshot["tables"]) == set(TABLES), "Snapshot tables changed: update schema definitions first."
    for name, definition in TABLES.items():
        assert {c["name"] for c in definition} == set(snapshot["tables"][name]["columns"]), f"Columns changed: {name}"
        assert len(definition) == len(snapshot["tables"][name]["columns"])
    migration_names = sorted(row["migration"] for row in snapshot["tables"]["migrations"]["rows"])
    assert migration_names == sorted(p.stem for p in (ROOT/"database"/"migrations").glob("*.php")), "Repository migrations differ from backup."
    for r in RELS:
        parentcol = next(c for c in TABLES[r["parent"]] if c["name"] == r["parent_column"])
        childcol = next(c for c in TABLES[r["child"]] if c["name"] == r["child_column"])
        assert "PK" in parentcol["keys"] or "UK" in parentcol["keys"]
        if not r["logical"]:
            assert "FK" in childcol["keys"] and childcol["nullable"] == r["optional_parent"]
        assert ("UK" in childcol["keys"] or "PK" in childcol["keys"]) == r["unique_child"]

    OUT.mkdir(parents=True, exist_ok=True)
    PDF.parent.mkdir(parents=True, exist_ok=True)
    counts = {name: len(table["rows"]) for name, table in snapshot["tables"].items()}
    total_cols = sum(len(cols) for cols in TABLES.values())
    created = datetime.fromisoformat(snapshot["created_at"]).astimezone(timezone(timedelta(hours=8))).strftime("%Y-%m-%d %H:%M:%S +08:00")
    pdf = canvas.Canvas(str(PDF), pagesize=(W,H), pageCompression=1)
    pdf.setTitle("Paolo Paolo - Updated Database ERD - 10 October 2026")
    pdf.setAuthor("Paolo Paolo database documentation")
    pdf.setSubject("Schema reconstructed from JSON column metadata and matching Laravel migrations")
    pages = [
        ("01-business-erd", "Inventory & sales", "The eight core entities for products, stock batches, sales and staff.", business),
        ("02-security-erd", "Accounts & security", "Account management, recovery and session relationships, including optional references.", security),
        ("03-framework-tables", "Framework tables", "Laravel cache, queues and migration bookkeeping. No declared FK relationships.", infrastructure),
    ]
    for i, (filename, title, subtitle, draw) in enumerate(pages, 1):
        scene = Scene(pdf)
        header(scene, i, title, subtitle, created, total_cols)
        draw(scene, counts)
        scene.save(filename+".svg", title)
        pdf.showPage()
    pdf.save()

    for names, filename, title in [
        (list(TABLES), "updated-erd.mmd", "Paolo Paolo - complete updated ERD"),
        (BUSINESS, "business-erd.mmd", "Paolo Paolo - inventory and sales"),
        (SECURITY, "security-erd.mmd", "Paolo Paolo - accounts and security"),
        (INFRA, "framework-tables.mmd", "Paolo Paolo - framework tables"),
    ]:
        (OUT/filename).write_text(mermaid(names,title), encoding="utf-8")
    metadata = dict(source_file=args.snapshot.name, source_sha256=hashlib.sha256(args.snapshot.read_bytes()).hexdigest(),
                    created_at=snapshot["created_at"], table_count=len(TABLES), column_count=total_cols,
                    schema_basis="Columns: backup. SQL Server types / constraints: matching local migrations. No live catalog audit.",
                    migrations=migration_names, tables={n:dict(columns=c,row_count=counts[n]) for n,c in TABLES.items()},
                    relationships=RELS)
    (OUT/"schema-metadata.json").write_text(json.dumps(metadata,indent=2)+"\n", encoding="utf-8")
    with (OUT/"data-dictionary.csv").open("w", newline="", encoding="utf-8-sig") as stream:
        fields = ["table", "column", "sql_server_type", "nullable", "keys", "references", "on_delete", "note"]
        writer = csv.DictWriter(stream,fieldnames=fields)
        writer.writeheader()
        for name, cols in TABLES.items():
            for c in cols:
                r = next((r for r in RELS if r["child"]==name and r["child_column"]==c["name"]), None)
                writer.writerow(dict(table=name,column=c["name"],sql_server_type=c["type"],nullable=c["nullable"],
                                     keys=",".join(c["keys"]),references=(r["parent"]+"."+r["parent_column"] if r else ""),
                                     on_delete=(r["on_delete"] or "logical reference only") if r else "",note=c["note"]))
    readme = f"""# Updated database ERD - 10 October 2026

Source: `{args.snapshot.name}` (snapshot timestamp `{snapshot['created_at']}`).

Coverage: **{len(TABLES)} tables, {total_cols} columns, 13 migration-declared foreign keys and 3 application-only references**.
Each backup table and column is represented exactly once in the complete Mermaid source and data dictionary.
The PDF has three A3 landscape pages; `users` is repeated between pages 1 and 2 for readability.

## Deliverables

- `../pdf/updated-erd-2026-10-10.pdf`: printable three-page vector ERD.
- `01-business-erd.svg`: core inventory and sales diagram; accompanying PNG is its rendered PDF preview.
- `02-security-erd.svg`: accounts, recovery, registration and sessions; accompanying PNG is its rendered PDF preview.
- `03-framework-tables.svg`: independent Laravel infrastructure tables; accompanying PNG is its rendered PDF preview.
- `updated-erd.mmd`: editable Mermaid ER diagram containing all 21 tables.
- `business-erd.mmd`, `security-erd.mmd`, `framework-tables.mmd`: smaller Mermaid diagrams.
- `data-dictionary.csv`: every column, SQL Server type, nullability, keys and relationship target.
- `schema-metadata.json`: schema-only metadata, migration names, snapshot hash and row counts. No record values.

## Source and interpretation

The JSON snapshot includes table names, column names and records, but no SQL DDL, types, indexes or foreign-key definitions.
Column coverage was checked against the backup. Types, primary/unique/foreign keys, nullability and delete actions were
reconstructed from the repository's 19 migration files, whose names exactly match the snapshot's migration history,
using Laravel's SQL Server grammar. This is a migration-derived schema document, not a live SQL Server catalog audit.
Manually altered production constraints cannot be proved from this snapshot alone.

Solid diagram links are migration-declared foreign keys. Purple dashed links are application references only;
they do not imply an SQL constraint. In the Mermaid sources, solid/dashed connectors use this same documented convention.
Cardinality symbols: `||` exactly one, `o|` zero or one, `o<` zero or many. The diagrams describe schema cardinality,
not minimum record counts imposed by application validation. `PK` means primary key, `FK` foreign key, `UK` unique key,
and `Y` in the final table column means NULL is allowed. Key columns are displayed first; original column order is not implied.

## Important current relationships

- One category/status can have zero or many products. Each product requires one category and one status.
- One product can have zero or many receiving batches and sale lines.
- One sale requires one cashier and one payment method. It can have zero or many stored sale lines at schema level.
- A receiving batch may have no user (`User_ID` is nullable, delete action SET NULL).
- `Expiration_Date`, `Has_Expiration`, `Condition`, `Remaining_Quantity`, cost and retail price belong to the receiving batch.
- There is no sale-line-to-stock-batch allocation table or FK. FIFO balance is stored in `Remaining_Quantity`.
- Products have `Image` and `Images`; the latter is JSON stored as `nvarchar(max)`. These contain image paths,
  while uploaded file bytes are outside the database snapshot.
- Categories support `Is_Archived`; user accounts support `username` and `is_active`.
- `email_change_codes.user_id` is UNIQUE, making the user's pending email code a zero-or-one relationship.
- `password_reset_requests` has two distinct user FKs: the requester and the optional approving admin.
  For SQL Server, `approved_by` uses NO ACTION on delete; requester `user_id` uses CASCADE.
- `sessions.user_id` is nullable and indexed but has no FK constraint. Reset-code/token emails reference users by
  application convention only; token email is a primary key, while multiple reset-code rows per email are allowed.
- Cache and queue tables have no declared FK relationships. Settings / backup schedules are not database tables in this snapshot.

## Regeneration

Run `scripts/generate-backup-erd.py` with the snapshot path using a Python runtime that provides `reportlab`.
If migrations or snapshot columns change, update the explicit schema definitions before regeneration.
Render the PDF with Poppler (`pdftoppm`) to update its PNG previews.
"""
    (OUT/"README.md").write_text(readme,encoding="utf-8")
    print(json.dumps(dict(pdf=str(PDF),table_count=len(TABLES),column_count=total_cols,
                          declared_fk_count=sum(not r["logical"] for r in RELS),logical_reference_count=sum(r["logical"] for r in RELS),
                          migration_count=len(migration_names),schema_column_match=True)))


if __name__ == "__main__":
    main()
