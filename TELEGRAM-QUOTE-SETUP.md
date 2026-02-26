# Telegram quote API – setup

When the user clicks **Get a Quote**, the form sends VRM, location and mobile number to your Telegram bot.

## Option A: PHP hosting (e.g. shared hosting)

1. Upload `send-quote.php` to the same directory as your `index.html` (or the root of your site).
2. Edit `send-quote.php` and set:
   - **BOT_TOKEN** – from [@BotFather](https://t.me/BotFather) (e.g. `123456:ABC-DEF...`).
   - **CHAT_ID** – your Telegram user ID or group ID (get from [@userinfobot](https://t.me/userinfobot), or add the bot to a group and use the group ID).
3. In `index.html` the script already uses `QUOTE_API_URL = 'send-quote.php'`. If the form is in a subfolder, use e.g. `'/send-quote.php'` or the full path to the script.

## Option B: Vercel

1. Deploy this folder to Vercel (the `api/` folder will become serverless functions).
2. In Vercel project **Settings → Environment variables** add:
   - **TELEGRAM_BOT_TOKEN** – your bot token from @BotFather.
   - **TELEGRAM_CHAT_ID** – your chat ID (number or string).
3. In `index.html` set `QUOTE_API_URL = '/api/send-quote'` (or the full URL of your deployed site + `/api/send-quote`).

After setup, submitting the form will send a message to your bot like:

```
New quote request
VRM: AB12 CDE
Location: London SW1
Mobile: 07123456789
```
