# Stripe – Estimate deposit (20%)

The **Estimate** page lets customers build a quote and pay a **20% deposit** via Stripe Checkout. The remaining balance is due on job completion.

## Setup

1. **Stripe account**  
   Sign up at [stripe.com](https://stripe.com) and get your **Secret key** (Dashboard → Developers → API keys): use `sk_test_...` for testing, `sk_live_...` for live.

2. **Configure the secret key** (one of):
   - **Recommended:** set environment variable `STRIPE_SECRET_KEY=sk_test_...` on your server (cPanel, PHP-FPM, or in `.env` if you load it in PHP).
   - **Or** add to `dynamic.json` (same file as Telegram):  
     `"stripeSecretKey": "sk_test_..."`  
     Do not commit this file or expose it to the frontend.

3. **Test**  
   Open `estimate.html`, add some services, fill in your details, and click **Pay 20% deposit**. You should be redirected to Stripe Checkout. Use test card `4242 4242 4242 4242` and any future expiry and CVC.

## Thank you page (after payment)

After payment, Stripe redirects to **`estimate-success.php`**, which:

- **Retrieves the Checkout Session** from Stripe and confirms payment status.
- **Sends a message to Telegram** (same bot/chat IDs as the quote form) with: payment status, session ID, deposit amount, estimate total, customer email, and timestamp.
- **Pushes a purchase event to Google Tag Manager** so Google Analytics 4 can record revenue (see below).

## Google Tag Manager & Google Analytics revenue

1. **Create a GTM container** at [tagmanager.google.com](https://tagmanager.google.com) and get your container ID (e.g. `GTM-XXXXXXX`).

2. **Add the container ID to `dynamic.json`**:
   ```json
   "gtmContainerId": "GTM-XXXXXXX"
   ```
   The thank you page will then inject the GTM snippet (head + body) and push a **`purchase`** event to `dataLayer` with:
   - `ecommerce.transaction_id` – Stripe session ID  
   - `ecommerce.value` – deposit amount (e.g. 10.00)  
   - `ecommerce.currency` – e.g. GBP  
   - `ecommerce.items` – one item: “Estimate deposit (20%)”

3. **In GTM**, create a **GA4 Event** tag that:
   - Fires on the **Custom Event** trigger with event name `purchase`.
   - In the GA4 tag, enable **“Send eCommerce data”** or map the event parameters from `ecommerce` (e.g. `value` → `ecommerce.value`, `currency` → `ecommerce.currency`, `transaction_id` → `ecommerce.transaction_id`).

4. In **GA4**, the `purchase` event will then show in **Monetization** and **Reports** so revenue is attributed correctly.

## Files

- `estimate.html` – Estimate form, calculator, and “Pay 20% deposit” button.
- `create-checkout-session.php` – Creates a Stripe Checkout Session; returns the session URL for redirect.
- `estimate-success.php` – Thank you page: fetches session, notifies Telegram, pushes GTM/GA4 purchase event.
- `estimate-success.html` – Static fallback (optional); normal flow uses `.php`.

## Flow

1. User fills estimate (services from `dynamic.json` + custom lines). Total and 20% deposit are calculated.
2. User enters name, email, phone and clicks **Pay 20% deposit**.
3. Frontend POSTs to `create-checkout-session.php` with `amount_pence`, `customer_email`, etc.
4. PHP creates a Stripe Checkout Session and returns `{ "url": "https://checkout.stripe.com/..." }`.
5. Browser redirects to Stripe; after payment, Stripe redirects to **`estimate-success.php?session_id=...`**.
6. Thank you page retrieves the session, sends details to Telegram, pushes `purchase` to GTM/GA4, and shows the thank you message.

## Go live

- Replace `sk_test_...` with `sk_live_...`.
- In Stripe Dashboard, complete activation and use live keys only in production.
