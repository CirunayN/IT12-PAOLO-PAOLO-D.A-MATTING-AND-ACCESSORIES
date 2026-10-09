# Paolo Paolo Matting and Accessories

Laravel inventory, stock-in, POS, reporting, employee access, and backup management.

## Start the application

On this computer, the application uses SQL Server Express at `.\SQLEXPRESS`, database `it12`, with Windows authentication. The SQL Server service starts automatically with Windows.

Double-click `start.bat`, or run:

```powershell
.\start.bat
```

Open http://127.0.0.1:8000. Keep the launcher running while using the app; press Ctrl+C to stop it. XAMPP and its MySQL/Apache servers are not required.

You can also use `composer start`. The launcher loads the Microsoft SQL Server PHP extensions from this project's private storage directory, so it does not require changing the global PHP installation.

## Offline use

Once PHP, its SQL Server drivers, and the local database are installed, double-click `start.bat` to use the app without internet. The application serves its styles, fonts, icons, images, and JavaScript from this project. Login and password recovery, inventory, stock receiving, POS, reports, and local backups use the local server and SQL Express database. Google Drive backup uploads require internet.

The compiled UI files are included in `public/assets/build`; keep this folder when copying or deploying the application. Normal startup does not need npm, Node.js, a Vite server, Bootstrap/Tailwind CDN access, or Google Fonts. The app uses Tailwind rather than Bootstrap, and Tailwind is compiled into a local stylesheet. Third-party asset licenses are included in the bundle's `licenses` folder.

After changing a Blade template, Tailwind configuration, or frontend source, developers can rebuild the included assets:

```powershell
npm ci --ignore-scripts
npm run build
```

Package installation needs internet the first time. The finished build works offline and is committed with the application. The local asset links include a build timestamp so browsers load updated styles after a rebuild.

Asset references: [Tailwind compilation](https://v3.tailwindcss.com/docs/installation), [Font Awesome self-hosting](https://docs.fontawesome.com/web/setup/host-yourself/webfonts), [Fontsource self-hosted fonts](https://fontsource.org/docs/getting-started/install).

## Set up another Windows computer

Install PHP 8.3, 8.4, or 8.5, Composer, SQL Server Express, and Microsoft ODBC Driver 17.8+ or 18. Configure the SQL Server instance to start automatically as a Windows service and grant your Windows account access to the app database.

```powershell
composer install
Copy-Item .env.example .env
.\scripts\setup-sqlserver.ps1
.\scripts\php.ps1 artisan key:generate
```

Create an empty `it12` database in SQL Server Management Studio if it does not exist, then:

```powershell
.\scripts\php.ps1 artisan migrate
.\start.bat
```

For a completely empty development database, run `.\scripts\php.ps1 artisan db:seed` to load the project's sample accounts and inventory. Keep your migrated database as-is.

The driver installer downloads Microsoft's version 5.13.3 archive and checks its published SHA-256 checksum. Driver files and generated PHP configuration stay in `storage/app/sqlserver-setup`, which Git ignores. A clone needs to run the installer once.

The local `.env` configuration is:

```dotenv
DB_CONNECTION=sqlsrv
DB_HOST='.\SQLEXPRESS'
DB_PORT=
DB_DATABASE=it12
DB_USERNAME=null
DB_PASSWORD=null
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=yes
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

The empty port lets the SQL Server driver resolve the named local instance. Windows authentication uses the account running PHP. `DB_TRUST_SERVER_CERTIFICATE=yes` is for the local instance's self-signed certificate; use a trusted certificate and `no` for a remote production server.

Use the PHP wrapper for database-related commands:

```powershell
.\scripts\php.ps1 artisan migrate:status
.\scripts\php.ps1 artisan queue:listen
```

`composer dev` also loads the project-local drivers for the server, queue worker, and logs, and starts Vite after installing the npm dependencies.

## Existing data and backups

The migration to SQL Express preserves the existing accounts, product images, inventory batches, sales, and security records. The original MySQL database remains available in XAMPP, but the app now reads and writes SQL Express; subsequent changes are not synchronized back to MySQL.

Before switching, raw MySQL data files and a logical snapshot were saved under `storage/app/db-recovery`. The old `.env` is saved there as `.env-before-sqlserver`. These private files are ignored by Git.

With SQL Server, the Backup page creates application `.json` snapshots. Restore checks that tables and columns match the installed app schema and restores records and identity values in a transaction. A failed integrity check rolls the restore back. MySQL `.sql` backups must be restored using the original MySQL database; they cannot be uploaded as SQL Server snapshots. Apply the app migrations before restoring a snapshot to a new database.

To change the backup destination, click **Choose Folder**, browse the available drives and folders, select **Use This Folder**, then **Save Settings**. The picker lists folders on the computer running the app and supports empty folders. Cancel leaves the current destination unchanged.

## SQL Server integration tests

These tests cover inventory filters, checkout, identity-preserving restore, rejection of incomplete backups, and rollback when backup references are invalid. Use a disposable database whose name ends in `_sqlserver_test`; the tests recreate its schema.

```powershell
sqlcmd -S '.\SQLEXPRESS' -E -C -Q "IF DB_ID(N'it12_sqlserver_test') IS NULL CREATE DATABASE [it12_sqlserver_test]"
$env:SQLSERVER_TEST_DATABASE = 'it12_sqlserver_test'
.\scripts\php.ps1 vendor/phpunit/phpunit/phpunit --filter SqlServerDatabaseTest
Remove-Item Env:SQLSERVER_TEST_DATABASE
```

The existing general test suite still includes tests for older registration, verification, and profile routes that the app no longer exposes.

Microsoft references: [PHP driver installation](https://learn.microsoft.com/en-us/sql/connect/php/loading-the-php-sql-driver), [Windows authentication](https://learn.microsoft.com/en-us/sql/connect/php/how-to-connect-using-windows-authentication).

## Dashboard, inventory and reports

Dashboard From/To dates apply immediately to sales and recent transactions. Stock totals and the Low / Out of Stock list show current sellable inventory. Total Items opens Inventory. Inventory and receiving filters apply on selection; typing a search applies after a short pause. Inventory ordering offers newest/oldest, name, sellable stock and current retail price in either direction. Print and PDF exports keep the chosen order.

Login includes Forgot password for the existing offline recovery flow. The main page and navigation use the supplied landscape and sidebar images from local `public/images/scenery` files, with theme-aware fade overlays and opaque content cards to keep text readable. Decorative backgrounds are hidden when printing.

POS starts with a 70% product grid and 30% cart. The Layout slider saves the chosen split in the browser. On smaller screens the cart follows the grid. Open POS and Receive Stock are in the top bar and hide on their respective pages.

Receive New Shipment supports creating products, adding categories and dropping up to five product photos. Edit Product includes each stock batch's received quantity, cost, retail price, expiration and condition. Quantity corrections preserve units already sold and the original processor.

Print actions outside POS offer Print or Download PDF. Inventory and receiving exports include all records matching the selected filters, including records beyond the current page. PDF generation uses installed local libraries and works offline.

## Scheduled backups and folder selection

Manage Categories in Inventory or View / Manage Categories on Dashboard opens a modal with Active and Archived lists. Administrators can add, rename, archive and restore categories. Archiving preserves existing products and history; archived categories remain available in inventory filters but cannot be assigned to new products. An existing product can keep its archived category when editing other details. Backups from before category archiving restore those categories as active.

Start with `start.bat` or `composer start` so the server and background backup scheduler run together. In Database Backup, choose Automatic, the frequency and a time in Philippine time, then Save Settings. The scheduler checks every minute. Keep the computer and app running; a missed backup runs on the next check after the selected time. Manual backups do not change the automatic schedule. Failed snapshots are retried; only completed snapshots update backup history. Retention only deletes this application's `backup_p7db_` files.

Choose Folder opens the native Windows folder dialog on the server computer. Access the app through `127.0.0.1` or `localhost` for this feature, select a folder and Save Settings. Cloud uploads require internet when enabled. Worker logs are in `storage/logs/backup-worker.log` and `backup-worker-error.log`.

Feature validation against a disposable SQL Server database:

```powershell
$env:SQLSERVER_TEST_DATABASE = 'it12_sqlserver_test'
.\scripts\php.ps1 vendor/phpunit/phpunit/phpunit --filter 'ApplicationEnhancementsTest|BackupScheduleTest|SqlServerDatabaseTest'
Remove-Item Env:SQLSERVER_TEST_DATABASE
```
