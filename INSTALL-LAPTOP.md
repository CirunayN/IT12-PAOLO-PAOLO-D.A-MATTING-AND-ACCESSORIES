# Install on the shop laptop

Use a 64-bit Intel/AMD laptop running Windows 10 (build 19041 or newer) or Windows 11. Connect its charger and internet for the first installation. Windows administrator approval is required to install the Microsoft prerequisites. Normal daily use is offline and does not require administrator approval.

1. Put the separate **install-laptop.bat** beside your system ZIP. You may ZIP the entire project folder; the application can be at the ZIP root or inside one containing folder. Keep the updated `scripts` folder and compiled `public/assets/build` files in your ZIP.
2. Double-click the separate **install-laptop.bat**, select your ZIP, and choose an **empty permanent local folder**, such as `C:\PaoloPaolo`. The BAT extracts the required application files and opens setup. Approve the Windows installation prompt, read the software terms, and type **INSTALL** to continue. Alternatively, extract the complete ZIP yourself and run the BAT inside the extracted project.
3. Wait for setup to finish. It downloads missing prerequisites from their official publishers. SQL Express can take several minutes. If Windows requests a restart, restart the laptop and run **install-laptop.bat** again.
4. Setup creates the new **paolo_paolo_store** database and retains only the prepared administrator account, with its existing username and password. No old inventory, sales, categories or employee accounts are imported. For a developer installation without a prepared administrator transfer, setup asks for a first owner instead.
5. Open **Paolo Paolo** from the desktop shortcut, or double-click **start.bat**. Sign in with the retained administrator login and enter the new store data.
6. Open **My Account > Password** and generate and save a private recovery code. In **Database Backup**, select your desired backup time and folder. A new installation defaults to a daily backup at **6:00 PM laptop time**.

Keep the launcher window open and the laptop awake while using the system and for automatic backups. The browser can be closed. The backup worker checks the laptop clock every second. Expiry alerts appear in the administrator's notification bell for stock expiring within 30 days.

The standalone BAT also accepts a ZIP dragged onto it. If installation stops, run it again with the **same ZIP and destination folder** to resume. It rejects a different ZIP in an occupied folder. Use an empty folder for a new installation. Do not run the BAT directly from the ZIP viewer.

Extraction omits the old computer's real `.env`, generated caches, logs, installed `vendor`/`node_modules`, database files, old product photos, private backups, and private runtime. It retains the prepared administrator transfer only. Setup downloads and installs fresh dependencies for the laptop. Therefore the first setup still needs internet, even when your ZIP contains the whole system.

## What setup prepares

- A project-private PHP 8.4 runtime and HTTPS certificates, without replacing another program's PHP.
- Microsoft Visual C++ runtime, ODBC Driver 18, and SQL Server Express 2022 when needed.
- The Microsoft PHP SQL Server drivers and application dependencies from `composer.lock`.
- A fresh local SQL Express shop database, required empty tables, statuses, and Cash/GCash payment methods.
- Access to that application database for the Windows account that started the installer.
- The retained administrator login, local backup configuration, startup checks, and a desktop shortcut.

Node.js, npm, XAMPP, MySQL, and SQL Server Management Studio are not needed for normal installation or use. The compiled interface files are included. The web server listens on `127.0.0.1`; setup does not add firewall rules or remote SQL access.

## Fresh data and the retained administrator

This deployment starts with **one administrator and no old business records**. Its username, password hash, name and account email are transferred; employee accounts, recovery codes, login sessions and password-reset tokens are excluded. Generate a new recovery code after signing in. The original developer database is not cleared or copied. Do not restore an old full JSON backup into this fresh installation: that would bring back the old records.

Fresh setup defaults to `DB_DATABASE=paolo_paolo_store`, separate from the developer's `it12`. If a first installation finds business records or different accounts in its chosen database, it stops without deleting them. Select a new empty database in the destination `.env` and rerun setup. After a successful installation, rerunning setup preserves the new shop records, accounts, passwords and backup preferences; it does not reapply the old administrator password.

Before manually zipping the updated project, prepare or refresh the administrator transfer from the source computer:

```powershell
.\scripts\php.ps1 artisan app:export-laptop-admin --username=admin
```

The prepared file is `storage/app/laptop-setup/admin-account.json`. Include it when zipping the whole project. It is private and ignored by Git, so a GitHub source ZIP alone does not include it. The package builder refreshes it automatically. If the administrator password changes before deployment, refresh the file and rebuild your ZIP. Keep this ZIP private because it contains the administrator's password hash. The installed transfer file is removed after initialization; the original ZIP still contains it.

## If setup stops

Read the displayed error and rerun the installer after correcting it. Downloaded prerequisites are cached. Installation steps are logged in `storage/logs/laptop-install`; passwords and recovery codes are not recorded there. SQL Server also maintains its own Setup Bootstrap logs under `C:\Program Files\Microsoft SQL Server`.

For a read-only prerequisite report, run:

```powershell
.\install-laptop.bat -CheckOnly
```

If an existing `.env` points to a remote database, SQL authentication, another SQL instance, or a system database, setup stops instead of changing that database configuration. This installer supports the local `SQLEXPRESS` instance with Windows authentication. If a previously installed SQL instance denies database creation, its SQL administrator must grant access; the installer does not bypass SQL authentication.

Keep the project folder at its installed location. After moving it, rerun setup to update its private runtime paths and desktop shortcut. If a different Windows account will operate the system, run setup from that account so its database access and shortcut are prepared.

## Verified publisher sources

[PHP Windows releases](https://www.php.net/~windows/releases/releases.json), [Microsoft SQL Express installation manifest](https://github.com/microsoft/winget-pkgs/blob/master/manifests/m/Microsoft/SQLServer/2022/Express/16.0.1000.6/Microsoft.SQLServer.2022.Express.installer.yaml), [Microsoft ODBC downloads](https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server), [Microsoft PHP SQL Server drivers](https://github.com/microsoft/msphpsql/releases/tag/v5.13.3), [Composer installer verification](https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md), and [curl certificate bundle](https://curl.se/docs/caextract.html).
