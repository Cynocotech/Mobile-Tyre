# Database Migration Guide

The app uses **database only** for all data. No jobs, drivers, quotes, or messages are stored in JSON/CSV files when the database is enabled.

## MySQL Setup (recommended for production)

1. **Create a MySQL database** (e.g. `mobile_tyre`).

2. **Configure in `dynamic.json`**:
   ```json
   {
     "useDatabase": true,
     "databaseDsn": "mysql:host=localhost;dbname=mobile_tyre;charset=utf8mb4",
     "databaseUser": "your_user",
     "databasePass": "your_password"
   }
   ```

3. **Run the schema** (first-time setup):
   ```bash
   mysql -u your_user -p mobile_tyre < database/schema-mysql.sql
   ```
   Or let the app create tables automatically on first connection.

4. **Run migration** (import existing JSON/CSV data):
   ```bash
   php migrate-to-db.php
   ```

## SQLite (default, for development)

1. **Enable in `dynamic.json`**:
   ```json
   "useDatabase": true
   ```

2. **Run migration**:
   ```bash
   php migrate-to-db.php
   ```

   This creates `database/mobile_tyre.sqlite` and imports from `jobs.json`, `customers.csv`, `drivers.json`, `quotes.json`, `driver_messages.json`.

## What Gets Migrated

- Jobs from `jobs.json` and `customers.csv`
- Drivers from `database/drivers.json` and `admin/data/drivers.json`
- Quotes from `quotes.json`
- Driver messages from `driver_messages.json`

## Database-Only Behaviour

When `useDatabase: true`:

- **Jobs** – stored in `jobs` table only (no `jobs.json` or `customers.csv`)
- **Drivers** – stored in `drivers` table only
- **Quotes** – stored in `quotes` table only (no `quotes.json`)
- **Driver messages** – stored in `driver_messages` table only

## Files

- `config/db.php` – Connection (SQLite or MySQL)
- `config/db-helpers.php` – Query helpers
- `database/schema.sql` – SQLite schema
- `database/schema-mysql.sql` – MySQL schema
- `migrate-to-db.php` – Import script

## Rollback

Set `"useDatabase": false` in `dynamic.json` to use JSON files again. Run migration first to export data back to files (manual step).
