# Instagram Automation

A Laravel application for Instagram comment and messaging automations. It receives Meta webhooks, stores events, matches active rules, and queues replies for delivery.

## What the application does

- Connects an Instagram professional account through Meta OAuth.
- Receives and verifies Meta webhook requests at `/webhooks/meta`.
- Stores webhook payloads in the database and processes them through Laravel's database queue.
- Matches comments against keyword rules.
- Sends public comment replies and private replies when Meta has granted the required capability.
- Provides management pages for accounts, media/resources, templates, and automation rules.

## Requirements

Install these before starting:

- PHP 8.2 or newer with `pdo_sqlite`, `mbstring`, and `openssl` enabled.
- Composer.
- Node.js and npm (only needed to build the frontend assets).
- SQLite, or another database supported by Laravel.
- A Meta developer account and an Instagram professional account for webhook testing.
- An HTTPS URL that Meta can reach. For local development, use Cloudflare Tunnel or ngrok.

On Windows, XAMPP's PHP can be used instead of a separate PHP installation. Replace `php` in the commands below with the full path to `php.exe` when needed.

## 1. Get the project

```bash
git clone https://github.com/YOUR_ACCOUNT/instagram-automation.git
cd instagram-automation
```

## 2. Install dependencies

```bash
composer install
npm install
```

## 3. Create the local environment

Copy the example file and generate an application key.

```bash
cp .env.example .env
php artisan key:generate
```

PowerShell equivalent:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Never commit `.env`. It contains credentials and is ignored by Git.

## 4. Create and migrate the database

The default configuration uses SQLite and a database-backed queue, cache, and session store.

```bash
touch database/database.sqlite
php artisan migrate
```

PowerShell equivalent:

```powershell
New-Item -ItemType File -Force database/database.sqlite
php artisan migrate
```

If you choose MySQL or PostgreSQL instead, change the `DB_*` values in `.env` before running the migration.

## 5. Configure `.env`

Set the application URL and Meta values. Do not paste real secrets into GitHub, issues, screenshots, or chat.

```dotenv
APP_NAME="Instagram Automation"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

META_GRAPH_VERSION=v26.0
META_GRAPH_API_URL=https://graph.facebook.com
META_APP_ID=your_meta_app_id
META_APP_SECRET=your_meta_app_secret
META_WEBHOOK_VERIFY_TOKEN=choose_a_private_verify_token
META_OAUTH_REDIRECT_URI=https://your-public-host.example/meta/callback
```

`META_APP_ID`, `META_APP_SECRET`, and `META_WEBHOOK_VERIFY_TOKEN` must all belong to the same Meta app. If you switch Meta apps, update all three values and reconnect the Instagram account.

## 6. Build the frontend

```bash
npm run build
```

During active frontend development, use `npm run dev` instead.

## 7. Start Laravel and the queue worker

Use separate terminals. The queue worker is required for webhook processing and outgoing messages.

Terminal 1:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2:

```bash
php artisan queue:work database --sleep=1 --tries=3 --timeout=90
```

Open `http://127.0.0.1:8000`, register a local user, and sign in.

## 8. Expose the app over HTTPS for Meta

Start Laravel before starting the tunnel.

With Cloudflare Tunnel:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

If `cloudflared` is stored locally, use its local path instead. The executable is intentionally ignored by Git and must be installed separately on each machine.

Copy the generated HTTPS hostname, for example:

```text
https://random-name.trycloudflare.com
```

Update `META_OAUTH_REDIRECT_URI` to:

```text
https://random-name.trycloudflare.com/meta/callback
```

A temporary tunnel hostname changes when the tunnel is restarted. Update both `.env` and Meta whenever it changes.

## 9. Configure the Meta app

In Meta for Developers, open the app whose ID is in `META_APP_ID`.

### App settings

1. Add the public hostname under **App domains**. Enter only the hostname, without `https://` or a path.
2. In Facebook Login for Business, add this exact valid OAuth redirect URI:

   ```text
   https://random-name.trycloudflare.com/meta/callback
   ```

3. Add the public privacy policy and data deletion URLs if Meta requests them:

   ```text
   https://random-name.trycloudflare.com/privacy-policy
   https://random-name.trycloudflare.com/data-deletion
   ```

### Webhooks

1. Open the app's Webhooks configuration.
2. Use this callback URL:

   ```text
   https://random-name.trycloudflare.com/webhooks/meta
   ```

3. Enter the exact value of `META_WEBHOOK_VERIFY_TOKEN` from `.env`.
4. Click **Verify and save**.
5. Subscribe to the Instagram/Page fields required by the app, such as comments/feed and messages.
6. Use Meta's **Test** action to send a sample event.

Do not test by opening the webhook URL in a normal browser. The browser sends an ordinary GET request; Meta's verification and webhook test actions are the meaningful checks.

For production users, Meta may require App Review, Advanced Access, business verification, and an app in Live mode. Development-mode testing is limited to approved testers and the capabilities Meta grants to the app.

## 10. Connect Instagram in the application

1. Open the application's **Accounts** page.
2. Click **Connect with Meta**.
3. Approve the requested permissions in Meta.
4. Return to the application and confirm the Instagram account appears.
5. Keep the queue worker running.

The OAuth routes are:

- `GET /meta/connect`
- `GET /meta/callback`

## 11. Create an automation

1. Open **Media & resources** and add the Instagram media.
2. Use the numeric Graph API media ID, not the Instagram permalink or username.
3. Create a message template containing the reply text or resource URL.
4. Open **Rules** and create an active rule.
5. Choose a comment keyword trigger, select the media if required, and choose the public reply/template.
6. Add a new comment from a separate approved tester account.

Comments authored by the connected account are ignored to prevent loops. A matching event is stored first and then processed by the queue worker.

## 12. Verify the installation

Run the automated tests:

```bash
php artisan test
```

Confirm the webhook route exists:

```bash
php artisan route:list --path=webhooks/meta
```

Expected route:

```text
GET|POST|HEAD  webhooks/meta  meta.webhook
```

Inspect logs while testing:

```bash
tail -f storage/logs/laravel.log
```

PowerShell:

```powershell
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

A successful POST normally produces these log entries:

- `Meta webhook signature validated`
- `Meta webhook request received`
- `Meta webhook event stored`
- `Meta webhook processing job dispatched`
- `Meta webhook event processed`

## Troubleshooting

### Meta says verification succeeded, but no database row appears

Verification is a GET request and does not create a webhook event row. Use Meta's webhook **Test** or send a real event; those are POST requests.

### `signature validation failed`

The app secret does not match the Meta app that sent the request. Check that the webhook URL, `META_APP_ID`, and `META_APP_SECRET` all refer to the same Meta app, then restart Laravel and reconnect the account.

### The request reaches Cloudflare but Laravel logs nothing

Check that Laravel is running on `127.0.0.1:8000` and the tunnel forwards to that exact address. Confirm the callback path is `/webhooks/meta` (plural `webhooks`).

### The webhook is received but automation does not run

Keep `php artisan queue:work` running. Check that the rule is active, the keyword matches, and the media resource uses the numeric Graph API media ID.

### Comment events work but private replies or DMs fail

The Laravel pipeline can receive and process the event, but Meta controls messaging capability and permission approval. App Review, Advanced Access, business verification, or Live mode may be required. This cannot be bypassed in application code.

### A tunnel URL changed

Update `META_OAUTH_REDIRECT_URI`, the Meta OAuth redirect allowlist, the Meta webhook callback URL, and the app domain. Reconnect the account if the OAuth redirect changed.

## Production checklist

Before deploying publicly:

- Use a stable HTTPS domain instead of a temporary tunnel.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Store secrets in the hosting provider's secret manager.
- Use a persistent database and a supervised queue worker.
- Run migrations during deployment.
- Configure log rotation and monitoring.
- Complete Meta's required review and verification steps for the permissions you use.
- Rotate any credential that was ever exposed.

## Security

Never commit `.env`, access tokens, app secrets, webhook verify tokens, SQLite files, runtime logs, or local binaries. The repository ignores these files by default. If a credential is exposed, revoke or rotate it immediately in Meta.

## License

This project is licensed under the MIT license.
