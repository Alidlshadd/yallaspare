# yalla-spare-project
YallaSpare is a modern auto spare parts management and marketplace platform built with Laravel. It includes inventory control, order management, dealer system, audit logs, and an integrated customer marketplace.

## Database Backup and Restore

This project now includes PowerShell scripts for MySQL backup/restore:

- Backup script: `scripts/db-backup.ps1`
- Restore script: `scripts/db-restore.ps1`
- Backup folder: `storage/backups/db`

### Create backup

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\db-backup.ps1
```

Optional custom output directory:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\db-backup.ps1 -OutputDir "C:\backups\yallaspare"
```

### Restore backup

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\db-restore.ps1 -BackupFile "C:\laragon\www\yallaspare\storage\backups\db\yalla_db_YYYYMMDD_HHMMSS.sql"
```

### Schedule automatic backups (Windows Task Scheduler)

Use this action:

- Program/script: `powershell.exe`
- Arguments: `-ExecutionPolicy Bypass -File C:\laragon\www\yallaspare\scripts\db-backup.ps1`
- Start in: `C:\laragon\www\yallaspare`

Recommended frequency:

1. Every day at least once.
2. Keep backups on another disk/cloud in addition to local machine.
