## Instagram Automation

Laravel application for Instagram comment and messaging automations. It receives Meta webhooks, stores events, matches active rules, and queues replies for delivery.

### Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- SQLite (the default local database)

### Local setup

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database/database.sqlite
php artisan migrate
npm install
npm run build
```

Keep `.env` local. It contains application and Meta credentials and is intentionally ignored by Git.

### Configuration

Set these values in `.env`:

```dotenv
META_GRAPH_VERSION=v26.0
META_GRAPH_API_URL=https://graph.facebook.com
META_APP_ID=your_meta_app_id
META_APP_SECRET=your_meta_app_secret
META_WEBHOOK_VERIFY_TOKEN=your_webhook_verify_token
META_OAUTH_REDIRECT_URI=https://your-public-host.example/meta/callback
```

The Meta callback URL is `/webhooks/meta`. It must be reachable over HTTPS. For local development, expose the Laravel server with a tunnel and use that tunnel hostname in both Meta and `META_OAUTH_REDIRECT_URI`.

Run the application and queue worker in separate terminals:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
php artisan queue:work --tries=3
```

### Testing

```powershell
php artisan test
```

The webhook pipeline logs verification, signature validation, receipt, storage, and processing in `storage/logs/laravel.log`. Runtime logs are ignored and must not be committed.

### Meta access limits

Comment webhook receipt and processing can be tested in development mode with an approved tester. Direct-message and private-comment-reply delivery still depends on Meta granting the required messaging capability and permissions; this is controlled by Meta, not by Laravel code.

### Security

Never commit `.env`, access tokens, app secrets, webhook verify tokens, database files, or runtime logs. If a credential is exposed, rotate it in Meta immediately.
