# Local development

The complete setup, Meta configuration, webhook verification, and troubleshooting guide is in the project [README.md](../README.md).

For a local run, use three terminals:

1. Start Laravel:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

2. Start the database queue worker:

   ```powershell
   php artisan queue:work database --sleep=1 --tries=3 --timeout=90
   ```

3. Give Meta a public HTTPS URL. You can use Cloudflare Tunnel, ngrok, or another tunnel that forwards to `http://127.0.0.1:8000`:

   ```powershell
   cloudflared tunnel --url http://127.0.0.1:8000
   ```

Cloudflare Tunnel is only one option for local development. If the application is hosted on a public HTTPS domain, use that domain and do not run a tunnel. Keep all credentials in `.env`; never commit or paste them.
