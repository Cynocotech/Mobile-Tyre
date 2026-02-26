# How to Upload to cPanel

Upload your Mobile Tyre site to any cPanel hosting (e.g. your domain’s shared hosting).

---

## 1. Files to upload

Upload these from your project:

| File / folder      | Required | Notes |
|--------------------|----------|--------|
| `index.html`       | ✅       | Main page |
| `styles.css`       | ✅       | Styles |
| `send-quote.php`   | ✅       | Sends form to Telegram (token already set) |
| `.htaccess`        | Optional | Forces HTTPS; helps avoid SSL/mixed-content errors |
| `api/`             | ❌       | Only for Vercel; not used on cPanel |

**Do not upload** (not needed on the server):

- `.env.example` – reference only
- `TELEGRAM-QUOTE-SETUP.md` – docs only
- `sina.html` – if it’s just a draft

---

## 2. Where to put them

1. Log in to **cPanel** (your host gives you the URL, e.g. `yourdomain.com/cpanel` or `cpanel.yourdomain.com`).
2. Open **File Manager**.
3. Go to **public_html** (this is the web root for your main domain).
4. Upload into `public_html` so you have:
   - `public_html/index.html`
   - `public_html/styles.css`
   - `public_html/send-quote.php`
   - `public_html/.htaccess` (optional; for HTTPS redirect)

If your site is in a **subfolder** (e.g. `public_html/mobiletyres/`), upload the same three files into that folder (and `.htaccess` there too if you use it).

---

## 3. How to upload

**Option A: File Manager**

1. In File Manager, go to `public_html`.
2. Click **Upload**.
3. Select `index.html`, `styles.css`, and `send-quote.php` (or drag and drop).
4. Wait until all uploads finish.

**Option B: FTP (FileZilla, etc.)**

1. In cPanel, open **FTP Accounts** and note:
   - Host: often `ftp.yourdomain.com` or your domain
   - Username / password (use the FTP account from cPanel)
2. In FileZilla: connect, go to `public_html`, then drag the three files into it.

---

## 4. File permissions

Set permissions so the web server can read (and run) your files but others can’t change them.

**Recommended permissions**

| Item            | Permission | Numeric | Notes |
|-----------------|------------|---------|--------|
| `index.html`    | rw-r--r--  | **644** | Owner read/write, others read |
| `styles.css`    | rw-r--r--  | **644** | Same as above |
| `send-quote.php`| rw-r--r--  | **644** | Same; PHP is executed by the server |
| `public_html` (folder) | rwxr-xr-x | **755** | Owner full, others read+execute (default is usually fine) |

**How to set in cPanel File Manager**

1. Go to **File Manager** → open the folder where the files are (e.g. `public_html`).
2. **Single file:** right‑click the file → **Change Permissions**.
3. **Numeric:** enter **644** (or tick: Owner Read+Write, Group Read, World Read).
4. Click **Change Permissions**.
5. Repeat for `index.html`, `styles.css`, and `send-quote.php`.

**How to set via FTP (FileZilla)**

1. Right‑click the file → **File permissions**.
2. Set **Numeric value** to **644** (or use the checkboxes: Owner 6, Group 4, Public 4).
3. Click OK.

**If something doesn’t work**

- **500 or “Permission denied” on send-quote.php:** ensure it’s **644**, not 600 or 000. Avoid **777** (insecure).
- **Folder not listable:** folder should be **755**; don’t use 777 for the web root.

---

## 5. Set the form to use your PHP script

In `index.html`, the form already posts to `send-quote.php`:

```javascript
var QUOTE_API_URL = 'send-quote.php';
```

- If the site is in the **root** of `public_html`: leave as above.
- If the site is in a **subfolder** (e.g. `public_html/mobiletyres/`), change to either:
  - `QUOTE_API_URL = '/mobiletyres/send-quote.php';`  
  or  
  - `QUOTE_API_URL = 'send-quote.php';`  
  (same folder as `index.html` is fine).

---

## 6. Check it works

1. Open your site: `https://yourdomain.com` (or `https://yourdomain.com/mobiletyres/`).
2. Fill the form (VRM, location, mobile) and click **Get a Quote**.
3. You should get “Thanks! We'll contact you shortly” and a message in Telegram (chat ID 1819809453).

If you get an error:

- **404 / “send-quote.php not found”** – `send-quote.php` is not in the same folder as the page, or the path in `QUOTE_API_URL` is wrong.
- **500** – PHP error: in cPanel **File Manager** check the file permissions (e.g. `644` for `send-quote.php`). In **Error Log** (cPanel) you’ll see the exact error.
- **“Server config missing”** – That message comes from the Vercel version; on cPanel you use `send-quote.php`, so make sure you’re not calling `/api/send-quote` and that the PHP file has the correct `$BOT_TOKEN` and `$CHAT_ID` inside it.

---

## 7. SSL / HTTPS (fixing SSL errors)

If you see **“SSL error”**, **“Mixed content”**, or **“Connection not secure”**, use these steps.

**A. Install or fix the SSL certificate (cPanel)**

1. In cPanel open **SSL/TLS Status** or **Let’s Encrypt SSL** (or **AutoSSL**).
2. Select your domain and run **Run AutoSSL** / **Install** so the domain has a valid certificate.
3. Wait a few minutes, then open `https://yourdomain.com` – the padlock should show.

**B. Force the site to use HTTPS**

So the form always posts over HTTPS (no mixed content), force HTTPS with `.htaccess`:

1. In **File Manager**, go to `public_html`.
2. If there is already an `.htaccess` file, open it to edit. If not, click **+ File**, name it `.htaccess`, create it, then edit.
3. Add or merge these lines at the **top** of `.htaccess`:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

4. Save. Now `http://yourdomain.com` will redirect to `https://yourdomain.com`.

**C. Form still fails with “SSL error” or “blocked”**

- **Mixed content:** The page must load over `https://`. If you open the site with `http://`, the browser may block the form POST when it goes to HTTPS, or the other way around. Use the redirect above so you always use HTTPS.
- **Self-signed certificate:** If your host gave you a temporary/self-signed cert, the browser will warn “Your connection is not private”. Use **Let’s Encrypt** (free) in cPanel so you get a trusted cert.
- **Wrong domain in certificate:** The cert must be for the exact domain you use (e.g. `www.yourdomain.com` vs `yourdomain.com`). In cPanel, issue the cert for the address you actually use, or add both.

**D. .htaccess file to upload (optional)**

We’ve added an `.htaccess` file in your project. Upload it into `public_html` (same folder as `index.html`). It forces HTTPS and helps avoid SSL-related form errors. If your host already has redirect rules, merge the **Force HTTPS** block above into your existing `.htaccess` instead of overwriting it.

---

## 8. Optional: use a subdomain or folder

- **Subdomain** (e.g. `tyres.yourdomain.com`): in cPanel create the subdomain; it will have its own folder (e.g. `public_html/tyres`). Upload the three files into that folder.
- **Subfolder** (e.g. `yourdomain.com/tyres`): create a folder like `public_html/tyres` and upload the three files there. Then use `https://yourdomain.com/tyres/` and, if needed, set `QUOTE_API_URL` as in step 4.

---

## Quick checklist

- [ ] `index.html`, `styles.css`, `send-quote.php` uploaded to `public_html` (or your subfolder)
- [ ] **Optional:** `.htaccess` uploaded to force HTTPS and reduce SSL errors
- [ ] **Permissions:** all three files set to **644**; folder **755**
- [ ] **SSL:** certificate installed (e.g. Let’s Encrypt in cPanel); site opens with `https://`
- [ ] `QUOTE_API_URL` in `index.html` points to `send-quote.php` (same folder or correct path)
- [ ] Form test: submit a quote and confirm the Telegram message arrives

Your Telegram token and chat ID are already set in `send-quote.php`; no need to change anything for cPanel unless you want to use different credentials.
