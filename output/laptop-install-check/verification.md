# Laptop installer verification

Verified on 15 October 2026.

- 81 targeted application tests passed (432 assertions), including first-owner setup, preservation of existing credentials and backup preferences, database-target validation, backup timing, expiry alerts, inventory, receipts, and restore authorization.
- Eight Windows PowerShell safety checks passed, covering fresh configuration, preservation of the existing application key, stale generated-cache removal, cached downloads, checksum rejection, and unsigned-installer rejection.
- The installer read-only prerequisite report passed on this computer without installations or database changes.
- Official PHP, Visual C++, ODBC, and SQL Express prerequisite downloads were verified using published checksums and/or Microsoft Authenticode signatures. The Microsoft installers were not executed.
- In a disposable folder whose path included spaces and an apostrophe, Windows PowerShell 5 successfully prepared PHP 8.4.26, verified and installed the Microsoft PHP SQL Server drivers, verified HTTPS certificates, installed 82 production dependency packages from composer.lock, and loaded the application framework and PDF library. The temporary runtime and dependency files were removed.
- A separate disposable SQL Server database was created, its Windows ownership/access was checked, and provisioning was rerun successfully. Only those generated test databases were removed afterward.
- PowerShell syntax and source whitespace checks passed.
- The deployment ZIP was checked for required application files and the absence of private .env files, active databases, backups, and development directories.

The complete UAC/Visual C++/ODBC/SQL Express installation sequence has not been executed on a clean Windows laptop or VM. Its first full installation test must be performed on the destination laptop. No Microsoft prerequisites were reinstalled on the developer's computer, and the live it12 database was not migrated, seeded, reset, or restored during these checks.
