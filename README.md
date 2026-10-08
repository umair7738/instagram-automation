# Instagram Automation

A Laravel application for Instagram comment and messaging automations. It receives Meta webhooks, stores events, matches active rules, and queues replies for delivery.

## Project stack

- Laravel 12 on PHP 8.2+
- MySQL or MariaDB
- Laravel database-backed queue, cache, and sessions
- Blade views with Bootstrap 5 loaded from a CDN
- Meta Graph API and Meta Webhooks

Node.js, npm, Vite, Redis, Docker, and SQLite are not required to run the current application. Some related files remain from the Laravel project scaffold, but the application pages do not depend on them.

## What the application does

- Connects an Instagram professional account through Meta OAuth.
- Receives and verifies Meta webhook requests at `/webhooks/meta`.
- Stores webhook payloads in the database and processes them through Laravel's database queue.
- Matches comments against keyword rules.
- Sends public comment replies and private replies when Meta has granted the required capability.
- Provides management pages for accounts, media/resources, templates, and automation rules.

## Requirements

Install these before starting:

- PHP 8.2 or newer with `pdo_mysql`, `curl`, `mbstring`, and `openssl` enabled.
- Composer.
- MySQL or MariaDB. XAMPP MySQL and phpMyAdmin are suitable for local development.
- A Meta developer account and an Instagram professional account for webhook testing.
- A public HTTPS URL that Meta can reach. This can be a hosted domain or a temporary tunnel during local development.

On Windows, XAMPP's PHP can be used instead of a separate PHP installation. Replace `php` in the commands below with the full path to `php.exe` when needed.

## 1. Get the project

```bash
git clone https://github.com/YOUR_ACCOUNT/instagram-automation.git
cd instagram-automation
```

## 2. Install dependencies

```bash
composer install
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

Start MySQL, then create an empty database named `instagram_automation`. You can create it in phpMyAdmin or run:

```sql
CREATE DATABASE instagram_automation
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Set the matching `DB_*` values in `.env`, then run:

```bash
php artisan migrate
```

The same MySQL connection stores application data, sessions, cache entries, and queued jobs.

## 5. Configure `.env`

Set the application URL and Meta values. Do not paste real secrets into GitHub, issues, screenshots, or chat.

```dotenv
APP_NAME="Instagram Automation"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=instagram_automation
DB_USERNAME=root
DB_PASSWORD=

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

## 6. Frontend assets

No frontend build is required. The authenticated application layouts and legal pages load Bootstrap 5 from a CDN and use server-rendered Blade templates. Node.js and npm are therefore optional and are not part of the normal setup.

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

## 8. Give Meta a public HTTPS URL

Meta cannot call `localhost` or `127.0.0.1`. It needs a public HTTPS address for OAuth redirects and webhooks.

If the application is deployed on a server with HTTPS, use that domain and skip tunneling completely.

For local development, start Laravel first and expose `http://127.0.0.1:8000` using any tunnel provider. Examples include Cloudflare Tunnel and ngrok.

Cloudflare Tunnel example:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

ngrok example:

```bash
ngrok http 8000
```

These tools are development conveniences, not application dependencies. Install and use whichever tunnel provider you prefer. A production deployment should use its own stable HTTPS domain.

Copy the generated HTTPS hostname, for example:

```text
https://your-public-domain.example
```

Update `META_OAUTH_REDIRECT_URI` to:

```text
https://your-public-domain.example/meta/callback
```

If a temporary tunnel hostname changes, update both `.env` and the matching Meta settings. A hosted domain normally remains unchanged.

## 9. Configure the Meta app

In Meta for Developers, open the app whose ID is in `META_APP_ID`.

### App settings

1. Add the public hostname under **App domains**. Enter only the hostname, without `https://` or a path.
2. In Facebook Login for Business, add this exact valid OAuth redirect URI:

   ```text
   https://your-public-domain.example/meta/callback
   ```

3. Add the public privacy policy and data deletion URLs if Meta requests them:

   ```text
   https://your-public-domain.example/privacy-policy
   https://your-public-domain.example/data-deletion
   ```

### Webhooks

1. Open the app's Webhooks configuration.
2. Use this callback URL:

   ```text
   https://your-public-domain.example/webhooks/meta
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

GitHub Actions uses a temporary SQLite database only for isolated automated tests. The application setup described above uses MySQL or MariaDB.

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

### The public URL receives a request but Laravel logs nothing

For local development, check that Laravel is running on `127.0.0.1:8000` and the chosen tunnel forwards to that exact address. For hosted environments, check the web server and PHP logs. Confirm the callback path is `/webhooks/meta` (plural `webhooks`).

### The webhook is received but automation does not run

Keep `php artisan queue:work` running. Check that the rule is active, the keyword matches, and the media resource uses the numeric Graph API media ID.

### Comment events work but private replies or DMs fail

The Laravel pipeline can receive and process the event, but Meta controls messaging capability and permission approval. App Review, Advanced Access, business verification, or Live mode may be required. This cannot be bypassed in application code.

### The public URL changed

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

Never commit `.env`, database exports, access tokens, app secrets, webhook verify tokens, runtime logs, or local binaries. The repository ignores these files by default. If a credential is exposed, revoke or rotate it immediately in Meta.

## License

This project is licensed under the MIT license.
