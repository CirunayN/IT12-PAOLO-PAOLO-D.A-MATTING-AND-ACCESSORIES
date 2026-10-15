# Offline backup and expiry verification

Verified on 15 October 2026.

- 66 targeted tests passed, with 387 assertions, covering scheduling, clock offsets, expiry boundaries, inventory, access control, backup restore authorization, and receipt behavior.
- In an isolated temporary SQLite database, the backup schedule was set to 19:10 using the settings screen. The browser was closed before that time. The background worker created one nonempty backup at 19:10:03 +08:00 and did not create duplicates on subsequent checks.
- The existing-server launcher path started the backup worker. After its test worker was stopped, the launcher restarted it automatically and still did not duplicate the completed backup.
- The notification panel displayed sample stock expiring in 7 and 30 days, excluding sample stock expiring in 31 days. Keyboard closing and the narrow-screen layout were checked.
- Windows PowerShell was unavailable in one PHP web-server environment. The native Windows time-bias fallback resolved the device offset without further warnings.
- A read-only query against the configured SQL Server database successfully retrieved the expiry summary using device time.
- PowerShell syntax validation and the source diff whitespace check passed.
- Temporary preview processes, database, backup, and helper files were removed. The store's existing backup schedule and business records were not changed.

The preview image contains disposable sample data. Restart `start.bat` to load the updated worker. In Database Backup, select Automatic, choose the time and frequency, and save. Keep the laptop awake and the launcher open; the browser can be closed.
