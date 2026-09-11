# Database backup and restore

Backups are intentionally infrastructure-dependent rather than implemented as an
application command. Database credentials and backup destinations must not be
handled by an HTTP-facing Laravel process.

Use the managed database provider's encrypted, automated backups and point-in-time
recovery in production. Verify retention, encryption, access controls, and restore
procedures with a scheduled restore drill.

For a MySQL-compatible database, an operator can create an encrypted dump through
the provider's secret manager and backup storage:

```sh
mysqldump --single-transaction --routines --triggers "$DB_DATABASE" | gzip | age -r "$BACKUP_AGE_RECIPIENT" > backup.sql.gz.age
```

Restore only into a maintenance environment after validating the dump:

```sh
age -d -i "$BACKUP_AGE_IDENTITY" backup.sql.gz.age | gunzip | mysql "$DB_DATABASE"
```

Never place credentials, encryption identities, or generated backups in the
repository. Restrict backup and restore commands to an audited operations role.
