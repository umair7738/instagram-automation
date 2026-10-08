# Local development

Start XAMPP MySQL, then run the app with `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000`.

Run queued webhook and outbound-message work in a second terminal with `C:\xampp\php\php.exe artisan queue:work database --sleep=1 --tries=3 --timeout=90`.

Start a temporary HTTPS development tunnel with `tools\cloudflared\cloudflared.exe tunnel --url http://127.0.0.1:8000`.

The application uses database-backed queues, cache, and sessions. Do not add Node, npm, Redis, Horizon, Docker, WSL, or frontend build tooling.

For Meta development, configure the public HTTPS tunnel URL as the OAuth redirect and webhook base URL. Keep the credential values in `.env`; never commit or paste them. Cloudflare Tunnel and ngrok both work because only the public base URL changes.

Webhook endpoint: `POST /webhooks/meta`.

OAuth endpoints: `GET /meta/connect` and `GET /meta/callback`.
