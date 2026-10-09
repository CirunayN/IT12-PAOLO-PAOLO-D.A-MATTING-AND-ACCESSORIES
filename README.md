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
