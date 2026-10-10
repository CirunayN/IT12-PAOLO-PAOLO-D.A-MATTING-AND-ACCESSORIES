# Simplified black-and-white ERD - 10 October 2026

Source: `backup_p7db_2026-10-10_051707_a825b96d.json`, snapshot timestamp `2026-10-09T21:17:09+00:00`.

The current draw.io file and PNG/SVG images use white table boxes, black text and black connectors. The first page shows all **21 tables**, all **144 columns**, all **13 migration-declared foreign keys** and **3 application references** together. Three additional detail pages make individual areas easier to read.

## Current deliverables

- `main-business-erd.drawio`: a separate, single-page ERD for documentation, showing the eight main business tables, 43 selected fields and eight foreign-key relationships.
- `main-business-erd.png` / `.svg`: the main business ERD as a high-resolution image or vector image.
- `main-business-drawio-preview.png`: the main business ERD displayed in draw.io.
- `updated-erd-2026-10-10.drawio`: native, editable tables, fields and relationship lines across four pages; the complete overview opens first.
- `00-all-tables-erd.png` / `.svg`: the complete ERD, with all 21 tables and 144 fields on one page.
- `01-business-erd.png` / `.svg`: the main inventory and sales ERD, with eight tables.
- `02-security-erd.png` / `.svg`: accounts, registration, password recovery and sessions.
- `03-framework-tables.png` / `.svg`: cache, queues and migrations.
- `drawio-preview.png`: the current ERD displayed in the official draw.io editor.
- `data-dictionary.csv` and `schema-metadata.json`: the complete source schema, including all 144 columns, types, nullability and key definitions.

The complete overview includes every source column, including creation/update timestamps. The three simplified detail pages omit routine timestamps from business/account tables, while retaining keys and important business fields, including batch quantities, prices, expiration and condition. SQL types and record counts are omitted from the diagrams; the dictionary and metadata retain the complete schema details. Earlier Mermaid sources and the previously generated detailed PDF are unchanged; use the current draw.io file or PNG/SVG images for this black-and-white version.

## Notation

- `PK`: primary key. `FK`: database foreign key. `UK`: unique key.
- Solid black lines: migration-declared foreign keys.
- Dashed black lines: application references without a database foreign-key constraint.
- Two bars: exactly one. Circle plus bar: zero or one. Circle plus crow's foot: zero or many.

Cardinality follows the schema, rather than minimum record counts enforced by application validation. Receiving batches can have no receiving user. Email-change codes allow at most one row per user. Password-reset requests separately reference their requesting user and optional approving admin.

## Database details preserved

Expiration, condition, remaining stock and cost/retail prices belong to `tbl_Stock_in`, rather than the product master. Products retain `Image` and `Images` path fields. There is no sale-line-to-stock-batch foreign key or allocation table in this snapshot; no such relationship is invented. Cache and queue tables have no declared foreign keys. Service/labor and backup-settings tables are not present in the backup.

The backup provides table/column names and records, but no SQL DDL. Types, constraints and cardinalities were reconstructed from the repository's 19 matching migrations. This remains a migration-derived schema document, not a live SQL Server catalog audit. No record values are included in the diagrams.

## Regenerate the simplified version

Run `scripts/generate-simple-erd.py` using Python with Pillow. It reads the saved schema metadata, regenerates the native draw.io file and all four PNG/SVG pages, and checks table/field coverage, relationship coverage, overlapping table boxes, text widths and connector placement. It does not change the application or database.

Use `scripts/generate-simple-erd.py --business-only` to regenerate only the separate main business ERD. It includes Users, Categories, Product Status, Payment Methods, Products, Stock Batches, Sales and Sold Items. Authentication/recovery, sessions, cache, queues and migrations remain documented in the complete version.
