# Fresh shop deployment with the retained administrator

Verified on 15 October 2026.

The deployment now exports exactly one active administrator's name, username, email and existing password hash into a Git-ignored local transfer file. It exports no business records, employee accounts, session tokens or recovery codes. The ZIP extractor also excludes old product photos and previous initialization state. A ZIP missing the transfer stops before extraction.

Fresh setup selects paolo_paolo_store rather than the developer database it12. Initial import refuses a database with business records or other accounts, without deleting them. Successful setup retains the original login, creates only required statuses/payment methods, writes a local initialization marker, and removes the installed credential transfer. Subsequent setup preserves new shop records and later password changes.

- Ten initialization/export tests passed with 76 assertions, including malformed/plaintext transfers, administrator selection, source preservation, refusal of existing data, and idempotent setup.
- Nine environment/prequisite safety checks and 17 ZIP checks passed under Windows PowerShell, including the actual standalone BAT.
- The real prepared administrator transfer was imported into a newly created disposable SQL Server database. Its username and password hash matched the source; all five business tables started empty, no other accounts or recovery codes were imported, and only required lookup defaults were present.
- A second setup preserved newly entered sample data and a changed test password. Source table counts and the source administrator password hash remained unchanged. Only the disposable database and temporary storage were removed afterward.
- The deployment ZIP excludes old product photos, actual .env files, active databases and backups. The administrator transfer is the sole intentional account-data exception. The ZIP must remain private.

The full Windows prerequisite installation still needs a destination-laptop run. These checks did not reinstall software or reset the shop/developer database.
