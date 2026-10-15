# Filter caching and ZIP installer verification

Verified on 15 October 2026.

- 95 affected application tests passed with 532 assertions against isolated test data. The 22 SQL Server-only tests skipped in that run were subsequently executed with the SQL Server suite: all 28 tests passed with 308 assertions. Combined: 123 relevant tests, 840 assertions.
- Two browser-memory unit tests passed. Production interface assets rebuilt successfully.
- Eight existing installer safety checks and 13 new ZIP installer checks passed under Windows PowerShell, including the actual self-contained BAT launched from a folder containing spaces and an apostrophe. No Microsoft installer was executed during these checks.
- ZIP checks cover nested project folders, omission of old .env/dependencies/database/backups, preservation of new laptop settings on resume, rejection of occupied destinations, duplicate case-insensitive paths, absolute paths and directory traversal.
- Repeating inventory filters produced no product, stock, category or status queries. Repeated receiving, dashboard, reports and transaction reads also avoided business-data queries. Tests cover expiration, user separation, stock edits, rollback/commit invalidation, current-data exports, and agreement between loaded and database-calculated stock/prices for expired, damaged and valid batches.
- Disposable SQL Server tests verified checkout, inventory, successful restore cache invalidation, and retention of the prior cache revision after a failed restore. The generated database was removed; the shop's it12 records were not migrated, seeded, reset or restored.
- In a temporary browser preview, filtering Engine Oil 03, then 04, then returning to 03 generated exactly one server request for each combination during that sequence. The return used browser memory. Pagination, browser Back, export links and receiving detail actions worked after results were replaced.
- Final preview: filter-cache-preview.png, using temporary sample products only. The preview tab, server and temporary SQLite database were removed afterward.
- The deployment ZIP was rebuilt. The ZIP and separate install-laptop.bat were copied to Downloads and both copies matched their source SHA-256 hashes.

A broader legacy test selection also encountered 31 existing errors in SystemAuditTest and ProductImagePresentationTest: those tests explicitly expect the former MySQL database and attempt to connect to a MySQL host derived from the current SQL Server configuration. They did not exercise the changed functionality. No production database configuration was changed to accommodate them.

The complete prerequisite installation still needs its first end-to-end run on the destination Windows laptop. Initial setup requires internet and administrator approval; ordinary use remains offline.
