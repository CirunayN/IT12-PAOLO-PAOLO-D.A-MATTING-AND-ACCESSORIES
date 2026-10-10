# September–October 2026 sample history

Run from the project folder with an existing active administrator:

```powershell
.\scripts\php.ps1 artisan db:seed --class=SeptemberOctober2026Seeder --force
```

This additive seeder covers September 1 through October 10, 2026. Opening stock is received on Sunday, August 30; subsequent receiving dates are September 6, 13, 20, 27 and October 4. New products arrive across four deliveries. Sales have varied times, basket sizes, quantities, active staff and Cash/GCash payments.

The [catalog](data/automotive-catalog-2026.json) contains real product names and public Philippine retail reference prices checked on October 10. Supplier costs, receipts, stock quantities and sales are simulated. GCash references start with `DEMO-SEP2026-`. No product photos or actual manufacturer lot expiration dates are supplied by this dataset.

Existing business records, employee accounts and batch balances are preserved. New sales consume only this run's batches using FIFO. Before insertion, the seeder writes an application database snapshot under `storage/app/backups`. Records are inserted in one transaction and validated for chronological stock availability and stock balance.

The database-specific manifest under `storage/app/seed-history` records inserted IDs, FIFO allocations, the random seed, a daily summary and the backup path. It prevents a second run from adding duplicate history. Keep this manifest with the database. Both the manifest and backup are ignored by Git.

The feature test uses the separate SQL Server test database and checks preservation, Sunday receiving, historical availability, receipt totals, cash change, GCash payments and repeat-run protection.
