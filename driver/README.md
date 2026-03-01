# Driver Portal

Driver onboarding, login, dashboard, and job management.

## Pages

- **onboarding.html** – New driver sign-up (name, email, licence, van) + embedded Stripe Connect payout setup (all on-site)
- **login.php** – Email/password or PIN login
- **dashboard.php** – Assigned jobs, update location, upload proof, mark cash paid
- **profile.php** – View driver details
- **connect-return.php** – Handles return from Stripe Connect onboarding

## APIs

- `api/register.php` – Create driver + Stripe Connect account (returns accountId, driverId for embedded onboarding)
- `api/account-session.php` – Create AccountSession for Connect embedded components
- `api/config.php` – Returns stripePublishableKey for client-side
- `api/jobs.php` – List jobs (GET), location/proof/cash_paid (POST)
- `api/location.php` – Update driver’s general location

## Data

- **database/drivers.json** – Driver records (id, email, password_hash, pin_hash, name, phone, licence, van, stripe_account_id, etc.)
- **database/jobs.json** – Jobs with `assigned_driver_id`, `payment_method`, `cash_paid_at`, `proof_url`
- **database/proofs/** – Job completion photos

## Stripe Connect

Requires Stripe secret key in `dynamic.json`. Drivers onboard as Express connected accounts for payouts.

## Push notifications (optional)

To add push notifications when jobs are assigned:

1. Generate VAPID keys: `npx web-push generate-vapid-keys`
2. Add `vapidPublicKey` to dynamic.json
3. Create `api/push-subscribe.php` to store subscriptions
4. When admin assigns a driver, send web push via `web-push` (PHP) or a small Node script
5. In dashboard, add `navigator.serviceWorker.ready.then(reg => reg.pushManager.subscribe(...))`
