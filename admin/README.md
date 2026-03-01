# Admin Panel

Admin dashboard for No 5 Tyre & MOT mobile tyre site.

## Access

Open `https://your-domain.com/admin/` (or `/admin/login.php`).

**Default password:** `password` (from config.example.json – copy to config.json)  
Change it: edit `admin/config.json` and set `passwordHash` to the output of:
```bash
php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"
```

## Sections

### Dashboard
- Total deposits (count and value)
- Deposits in last 7 and 30 days
- Jobs and quotes count
- Recent deposits table

### Services
- Add, edit, delete estimate services
- Fields: key, label, price, description, enable/disable
- SEO: meta title, description, OG image
- Icon: wrench, circle, bolt, truck, tool
- Saves sync to `dynamic.json` (prices + services array)

### Settings
- All `dynamic.json` fields:
  - Labour price, gallery images
  - Telegram (bot token, chat IDs)
  - Stripe (publishable, secret keys)
  - SMTP (host, port, user, pass, encryption)
  - VAT (number, rate)
  - Driver scanner URL
  - GTM container ID

### Drivers & Vans
- Add drivers with name, phone, van, van registration, notes
- Jobs list from `database/jobs.json` / `database/customers.csv`
- Ref, vehicle, postcode, amount

## Data files

- `admin/config.json` – password hash, session timeout
- `admin/data/services.json` – full service definitions
- `admin/data/drivers.json` – drivers and vans
- `database/customers.csv` – paid deposits (from Stripe success)
- `database/jobs.json` – job lookups for verify
- `database/quotes.json` – quote requests (from send-quote.php)
