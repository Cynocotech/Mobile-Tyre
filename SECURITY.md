# Security Measures

This document summarises security hardening applied to the Mobile Tyre application.

## Authentication & Session

- **Session hardening**: `cookie_httponly`, `cookie_samesite=Strict`, `use_strict_mode`
- **Session regeneration**: New session ID on admin login to prevent fixation
- **Session timeout**: Configurable via `admin/config.json` (`sessionTimeout`)
- **Password validation**: Length limit (256 chars) to mitigate DoS

## CSRF Protection

- **Admin login**: CSRF token required; validated before password check
- **Token storage**: Session-based; regenerated after successful login
- **SameSite cookies**: Cross-site requests don't send session cookie (additional protection for admin APIs)

## Security Headers

Applied across admin and public endpoints:

- `X-Content-Type-Options: nosniff` – Prevents MIME sniffing
- `X-Frame-Options: SAMEORIGIN` – Mitigates clickjacking
- `X-XSS-Protection: 1; mode=block` – Legacy XSS filter
- `Referrer-Policy: strict-origin-when-cross-origin` – Limits referrer leakage

## Input Validation & Sanitization

- **References**: Numeric only, max 12 digits
- **Session IDs**: Stripe format `cs_[a-zA-Z0-9_]+` enforced on `verify.php`, `pay-balance.php`
- **Driver IDs**: Alphanumeric plus `_-` only (`safe_id()`)
- **Profile data**: Name/phone length limits; control characters stripped
- **Connect return state**: Sanitized to alphanumeric + `_-`

## Path Traversal Prevention

- **proof.php** (admin): `safe_path_under()` ensures resolved path is under project root; `proof_url` format validated
- **driver-insurance.php** (admin): Same pattern for insurance documents
- **serve-proof.php** (driver): `realpath()` check; `proof_url` format validated

## XSS Prevention

- **PHP output**: `htmlspecialchars()` on all user-supplied data
- **JavaScript `innerHTML`**: Custom `escape()` / `esc()` functions escaping `& < > " '`
- **Dashboard, Jobs, Reports, Drivers**: All dynamic content escaped before insertion
- **Map popups**: Driver names escaped

## Credentials & Configuration

- **send-contact.php**: Uses `dynamic.json` for Telegram credentials (no hardcoded token)
- **Sensitive config**: Store in `dynamic.json` or `.env`; keep out of version control

## Recommendations

1. Change default admin password in production
2. Use HTTPS in production (enforce SLL/TLS)
3. Consider rate limiting on login and contact form
4. Keep `dynamic.json` and `admin/config.json` out of public web root if possible
5. Rotate API keys (Stripe, Telegram, VRM) periodically
