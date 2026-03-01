# Admin dashboard – database migration

Admin data is now stored in the database when `useDatabase` is true in dynamic.json.

## New tables

| Table           | Replaces                |
|----------------|-------------------------|
| `admin_settings`| admin/config.json       |
| `services`     | admin/data/services.json|
| `products`     | database/products.json  |
| `site_config`  | dynamic.json (admin-editable keys) |

Sensitive values (DB credentials, Stripe secret, Telegram token, SMTP password) remain in `dynamic.json`.

## Migration

1. Ensure `useDatabase: true` in dynamic.json and the database is configured.
2. Run the migration:
   ```bash
   php migrate-admin-to-db.php
   ```
3. This migrates:
   - Admin password and settings from config.json
   - Services from services.json
   - Products from products.json
   - Site config (prices, logo, etc.) from dynamic.json

## Behaviour

- **Admin auth**: reads password from `admin_settings` when useDatabase
- **Admin Settings, Services, Products**: read/write via DB
- **Site config**: `getDynamicConfig()` merges dynamic.json + site_config (DB overrides file)
- **Frontend (index, estimate)**: fetch `api-config.php` which returns public config from DB/file

Original JSON files are not modified by the migration. You can remove them after confirming everything works.
