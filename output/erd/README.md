# Updated database ERD - 10 October 2026

Source: `backup_p7db_2026-10-10_051707_a825b96d.json` (snapshot timestamp `2026-10-09T21:17:09+00:00`).

Coverage: **21 tables, 144 columns, 13 migration-declared foreign keys and 3 application-only references**.
Each backup table and column is represented exactly once in the complete Mermaid source and data dictionary.
The PDF has three A3 landscape pages; `users` is repeated between pages 1 and 2 for readability.

## Deliverables

- `updated-erd-2026-10-10.drawio`: three native, editable draw.io pages. Each table, field and connector can be edited; no embedded diagram images. Open this file in draw.io or diagrams.net.
- `drawio-preview.png`: preview captured from the official draw.io editor after opening the file.
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
Run `scripts/generate-drawio-erd.py` after the schema metadata is generated to recreate the native draw.io file.
