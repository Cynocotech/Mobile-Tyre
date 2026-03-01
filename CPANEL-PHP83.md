# cPanel & PHP 8.3 Setup

## PHP 8.3

1. **Select PHP 8.3** in cPanel → **MultiPHP Manager** → choose your domain → PHP 8.3
2. Optionally in **MultiPHP INI Editor**:
   - `max_execution_time` = 120 (for real-time streams)
   - `memory_limit` = 256M

## Real-time communication (Server-Sent Events)

The system uses **Server-Sent Events (SSE)** for instant updates:

- **Admin dashboard** – driver online/offline, new deposits, job assignments
- **Driver app** – new jobs, new messages, status changes

No WebSocket server or Node.js required. Works with standard PHP on cPanel.

### Endpoints

- `admin/api/stream.php` – admin stats stream
- `driver/api/stream.php` – driver jobs & messages stream

### Behaviour

- SSE runs for ~50 seconds then the client reconnects
- Updates are sent when `database/*.json` or `customers.csv` change
- Fallback: if EventSource fails, the dashboard falls back to polling every 15s

## Output buffering

If updates seem delayed, disable output buffering for the stream:

In `.htaccess` (or via cPanel PHP options):

```apache
<Files "stream.php">
  php_flag output_buffering Off
</Files>
```

Or in `admin/api/.htaccess` and `driver/api/.htaccess` if you use directory-specific configs.

## Troubleshooting

- **504 Gateway Timeout** – Increase `max_execution_time` to 120+
- **Stream not updating** – Check browser console for EventSource errors; verify session cookies
- **cPanel mod_security** – Some rules can block long-running requests; whitelist `stream.php` if needed
