# Troubleshooting Meta webhooks and Instagram automation

This guide is based on the failures encountered while configuring this project locally. Diagnose the pipeline in order. Do not change several settings at once, because a successful Meta verification request, a delivered webhook, a processed automation, and a sent Instagram message are four different results.

## Understand the request flow

```text
Meta
  -> public HTTPS URL
  -> GET or POST /webhooks/meta
  -> verify token or request signature validation
  -> webhook_events database row
  -> ProcessMetaWebhook queue job
  -> matching automation rule
  -> outgoing_messages database row
  -> SendInstagramMessage queue job
  -> Meta Graph API
```

Find the last successful step. The first missing step identifies the area to fix.

## Quick symptom table

| Symptom | Most likely cause | First check |
| --- | --- | --- |
| `/webhook/meta` returns 404 | Wrong singular path | Use `/webhooks/meta` |
| Opening `/webhooks/meta` returns 403 | Normal browser GET has no Meta verification parameters | Use Meta's **Verify and save** action |
| Meta verification fails | Callback URL or verify token mismatch | Check the exact URL and `META_WEBHOOK_VERIFY_TOKEN` |
| Meta says the test succeeded but no row appears | Only verification occurred, POST was rejected, or the wrong app was tested | Check `laravel.log` for `request received` or `signature validation failed` |
| `signature validation failed` | `META_APP_SECRET` does not belong to the app sending the webhook | Match the app ID and secret, then restart Laravel |
| Cloudflare/ngrok shows traffic but Laravel does not | Tunnel forwards to the wrong port or Laravel is stopped | Confirm forwarding to `http://127.0.0.1:8000` |
| Event is stored but remains `received` | Queue worker is not running | Start `php artisan queue:work` |
| Event is processed but no automation runs | Rule, keyword, account, or media ID does not match | Check the rule and numeric Graph media ID |
| Comment from the connected account does nothing | Loop protection intentionally ignores it | Test from a separate approved account |
| Public comment handling works but DM fails | Meta has not granted messaging capability | Check App Review and permission access |
| Meta returns error `(#3)` | App lacks the requested capability | Request the capability; code cannot bypass it |
| Meta returns error `(#190)` | Invalid, expired, empty, or wrong-host token | Reconnect using the correct OAuth flow |
| Meta returns error `(#200) pages_messaging` | Page messaging permission is missing | Add/approve the required permission or stop subscribing to that field |

## 1. Confirm the local application is running

From the project directory, start Laravel:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

The terminal must show:

```text
Server running on [http://127.0.0.1:8000]
```

On Windows, a project path containing spaces must be quoted. Run commands on separate lines:

```powershell
Set-Location "C:\path\to\instagram automation"
php artisan serve --host=127.0.0.1 --port=8000
```

Do not accidentally concatenate `Set-Location` and `php artisan` into one command. PowerShell then treats `php` as an argument to `Set-Location` and reports a positional-parameter error.

Confirm Laravel registered the correct route:

```powershell
php artisan route:list --path=webhooks/meta
```

Expected result:

```text
GET|POST|HEAD  webhooks/meta  meta.webhook
```

The same URL is intentionally used twice:

- Meta sends `GET /webhooks/meta` to verify the callback.
- Meta sends `POST /webhooks/meta` to deliver events.

Laravel selects the private verification or receiving handler based on the HTTP method.

## 2. Confirm the public HTTPS URL reaches Laravel

A tunnel is needed only when Laravel runs on a local computer. A deployed application with its own public HTTPS domain does not need Cloudflare Tunnel or ngrok.

Local options include:

```powershell
cloudflared tunnel --url http://127.0.0.1:8000
```

or:

```powershell
ngrok http 8000
```

The forwarding target must be exactly the Laravel address and port. Keep both the Laravel server and the chosen tunnel running.

On Windows, confirm the running Cloudflare process and forwarding command with:

```powershell
Get-CimInstance Win32_Process -Filter "Name='cloudflared.exe'" |
    Format-List ProcessId,CommandLine
```

Its command line should end with:

```text
tunnel --url http://127.0.0.1:8000
```

Temporary tunnel hostnames usually change when restarted. When the hostname changes, update all of these locations:

1. `META_OAUTH_REDIRECT_URI` in `.env`.
2. The Meta app's valid OAuth redirect URI.
3. The Meta webhook callback URL.
4. The Meta app domain, when required.

The URLs have different paths:

```text
OAuth redirect:  https://PUBLIC-HOST/meta/callback
Webhook:        https://PUBLIC-HOST/webhooks/meta
```

`/webhook/meta` is incorrect. The application route uses plural `webhooks`.

A Cloudflare message such as `context canceled` means a particular request ended before completion. It does not prove the webhook route is broken. Check the Laravel server terminal and application logs for the corresponding request.

A request line in the `php artisan serve` terminal proves that Laravel received the URL, but it does not prove verification, signature validation, database storage, or processing succeeded. Use `laravel.log` to identify the result.

## 3. Distinguish verification from webhook delivery

Meta callback verification is a GET request containing:

- `hub.mode=subscribe`
- `hub.verify_token`
- `hub.challenge`

When it succeeds, Laravel returns the challenge. The log shows:

```text
Meta webhook verification requested
```

Verification does not create a `webhook_events` row. A row is created only for a POST delivery.

Meta's field **Test** action should send a POST. A successful delivery produces:

```text
Meta webhook signature validated
Meta webhook request received
Meta webhook event stored
Meta webhook processing job dispatched
```

Opening the callback URL in a browser is not a valid webhook test. A normal browser request has no verification token, challenge, signature, or event payload. A 403 response in that situation is expected.

## 4. Fix verification-token failures

The token in Meta must exactly match `META_WEBHOOK_VERIFY_TOKEN` in `.env`. It is a private string chosen by the application owner; it is not an access token or the Meta app secret.

After changing `.env`, clear cached configuration and restart Laravel:

```powershell
php artisan optimize:clear
```

Then use Meta's **Verify and save** action again. The expected log context is:

```text
mode=subscribe
has_token=true
has_challenge=true
```

If `mode`, token, and challenge are all empty, the URL was opened manually or the request did not come from Meta's verification flow.

## 5. Fix webhook-signature failures

POST requests are authenticated with `X-Hub-Signature-256`, calculated using the secret of the Meta app that sent the request.

This log means Laravel received the request but rejected it before database storage:

```text
Meta webhook rejected because signature validation failed
```

Check the following:

1. `META_APP_ID` identifies the Meta app whose Webhooks page is being tested.
2. `META_APP_SECRET` is the current secret from that same app.
3. The webhook callback was configured inside that same app.
4. Laravel was restarted after editing `.env`.
5. Old Meta apps are not still sending tests to the same callback URL.

When switching from one Meta app to another, update the app ID and secret together, verify the callback again, and reconnect Instagram through the application. Changing only the app ID or only the secret creates a signature or OAuth mismatch.

Never print app secrets in logs or commit them to Git. If a secret is exposed, rotate it in Meta and update `.env`.

## 6. Understand the two OAuth/API families

Meta provides separate authentication models with different permissions and token hosts.

The primary flow currently documented by this project is Facebook Login for Business:

- Start route: `/meta/connect`
- Callback: `/meta/callback`
- API host: `graph.facebook.com`
- Permissions include `instagram_basic`, `instagram_manage_comments`, and `instagram_manage_messages`

The code also contains an optional Instagram Login flow:

- Start route: `/meta/connect/instagram`
- Callback: `/meta/callback/instagram`
- API host: `graph.instagram.com`
- Permissions use the `instagram_business_*` names

Do not mix a Facebook Login token with the Instagram Login host, or request `instagram_business_*` permissions through the Facebook Login flow. Choose one flow and use its matching app configuration, permissions, redirect URI, token, and API host.

This response usually indicates a mixed, empty, or expired token:

```text
(#190) Invalid OAuth access token - Cannot parse access token
```

Reconnect the account using the intended flow rather than copying tokens between tools.

## 7. Fix OAuth redirect and account-connection errors

If Meta reports that the redirect URL or domain is invalid:

1. Add the public hostname under Meta **App domains** without the scheme or path.
2. Add the full, exact URI under the login product's **Valid OAuth Redirect URIs**.
3. Ensure `META_OAUTH_REDIRECT_URI` is identical, including HTTPS, hostname, path, and trailing-slash behavior.

Example:

```text
https://PUBLIC-HOST/meta/callback
```

If Laravel says no connected Instagram professional account was returned, verify that:

- The Instagram account is Professional (Business or Creator).
- It is connected to the Facebook Page selected during OAuth.
- The authenticating Facebook user can manage that Page.
- The requested permissions were approved.

If OAuth was interrupted or the tunnel restarted during login, the cached OAuth state may expire. Start again from the application's **Connect with Meta** button.

## 8. Subscribe to the correct webhook object and field

The application can normalize comment events received through either route:

- Instagram object: `comments`
- Page object: `feed`, when the feed item is a comment

Instagram direct-message events use `messages` or `messaging`.

Which fields are available depends on the selected Meta product and login flow. Subscribe only to fields supported by that app. A field appearing in the dashboard does not guarantee that the app has permission to use it in production.

After account connection, inspect `laravel.log` for subscription attempts. A successful subscription records an HTTP 200 response. An HTTP 400/403 response includes Meta's reason.

## 9. Check logs in the correct order

Watch Laravel's main log:

```powershell
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

The diagnostic file is created lazily. Before the first webhook-related request, this command can correctly report that the file does not exist:

```powershell
Get-Content storage\logs\webhook-debug.log -Tail 20 -Wait
```

Use `laravel.log` first. After the diagnostic file exists, it shows compact entries for:

- `verify`: callback verification GET
- `signature_check`: webhook POST reached signature middleware
- `receive`: signature passed and the controller accepted the event

Interpret the stopping point:

- No new log anywhere: public URL did not reach this Laravel process.
- `verify` only: Meta verified the URL; no event POST was delivered.
- `signature_check` without `receive`: the app secret/signature did not match.
- `receive` without processing: check the database queue worker.
- Processed event without an outgoing message: check rule matching.
- Failed outgoing message: inspect the Meta API error.

## 10. Check webhook rows and duplicate handling

Every accepted POST is stored in `webhook_events`. The raw request body is hashed and used as an idempotency key, so sending the identical payload again does not create another event or rerun the automation.

In MySQL/phpMyAdmin, inspect recent rows with:

```sql
SELECT id, status, instagram_account_id, created_at, processed_at
FROM webhook_events
ORDER BY id DESC
LIMIT 20;
```

Common statuses:

- `received`: stored and waiting for queue processing.
- `processed`: the processing job completed.

Meta's synthetic sample payload may be stored successfully but still not match a real account, media resource, keyword, or automation rule.

## 11. Keep the queue worker running

The webhook controller returns quickly and dispatches background work. Without a worker, events remain stored but automations do not continue.

Run:

```powershell
php artisan queue:work database --sleep=1 --tries=3 --timeout=90
```

Useful checks:

```powershell
php artisan queue:failed
php artisan queue:retry all
```

Inspect queued jobs in MySQL:

```sql
SELECT id, queue, attempts, available_at, created_at
FROM jobs
ORDER BY id DESC;
```

If code or `.env` changes while the worker is running, restart the worker so it loads the new configuration.

## 12. Fix rules that do not trigger

For comment automation, confirm:

1. The rule is active.
2. Its trigger type is `comment_keyword`.
3. The comment contains the configured keyword. Matching is case-insensitive substring matching.
4. The selected media resource matches the webhook's numeric Graph API media ID.
5. The selected Instagram account matches the event's entry ID.
6. The comment came from another account.

Do not store an Instagram username, shortcode, or permalink as `instagram_media_id`. Retrieve the numeric media ID through the Graph API and match it to the permalink, for example:

```text
/{instagram-user-id}/media?fields=id,permalink&limit=100
```

The application intentionally logs and ignores comments authored by the connected Instagram account:

```text
Skipping Instagram comment authored by connected account
```

This prevents reply loops. Test using a separate approved account.

## 13. Diagnose outgoing replies

Inspect recent message records:

```sql
SELECT id, type, status, target_id, created_at, sent_at
FROM outgoing_messages
ORDER BY id DESC
LIMIT 20;
```

Meanings:

- `queued`: waiting for `SendInstagramMessage`.
- `sent`: Meta accepted the API call.
- `blocked`: no usable account token exists, policy rejected it, or the recipient is the connected account.
- `failed`: Meta returned an error; inspect `laravel.log` and the row's `meta` error data.

Public comment replies and private comment replies are different API operations. A working public reply does not prove that the app has Instagram Messaging capability.

## 14. Understand testers, Development mode, and Live mode

In Development mode, use accounts that have an accepted role/tester invitation for the correct Meta app. Adding a tester is incomplete until that Instagram/Facebook account accepts the invitation.

Use a separate tester account to comment on or message the connected professional account. Make sure the tester is signed into the account that accepted the invitation.

Publishing the app expands availability only after Meta's required reviews and access levels are satisfied. Publishing by itself does not grant messaging permissions.

Some Meta webhook screens explicitly warn that an unpublished app receives only dashboard sample tests and no production events. Follow the restriction displayed for the selected product and app mode.

Before Meta allows publishing, it may require the app's privacy policy, data deletion instructions, terms, icon, category, contact details, and use-case review. This application exposes the legal pages at:

```text
/privacy-policy
/data-deletion
/terms-of-service
```

## 15. Recognize Meta capability blockers

These errors occur after Laravel has correctly constructed and sent the API request.

### Error `(#3) Application does not have the capability to make this API call`

The Meta app has not been granted the required Instagram messaging capability. Confirm the correct messaging permission is requested for the chosen login flow:

- Facebook Login flow: `instagram_manage_messages`
- Instagram Login flow: `instagram_business_manage_messages`

Meta may require Advanced Access, App Review, a Live app, and business verification. Linking an unverified business portfolio or publishing the app does not automatically grant the capability.

There is no Laravel code change that can bypass this restriction. Until Meta grants it, comment webhooks and public replies can still be tested separately, while private replies and DMs can fail.

### Error `(#200) pages_messaging is required`

The app attempted to subscribe to or use a Page messages field without `pages_messaging`. Obtain the permission if that Page messaging feature is required. Instagram comment/feed subscriptions should not unnecessarily request Page message fields.

### Business verification is unavailable

If Meta requires business verification and the app owner cannot complete it, public/live DM automation cannot be enabled for arbitrary users through that app. Keep the implementation and webhook processing demonstrable with permitted comment/tester scenarios, or use an eligible verified business for production approval.

## 16. Safe app switching checklist

When switching Meta apps, change one coordinated set of settings:

1. Set the new `META_APP_ID`.
2. Set the matching `META_APP_SECRET`.
3. Confirm or update `META_WEBHOOK_VERIFY_TOKEN` in both `.env` and Meta.
4. Configure the current public OAuth redirect URI in the new app.
5. Configure and verify `/webhooks/meta` in the new app.
6. Subscribe the required fields in the new app.
7. Clear Laravel configuration and restart the server and worker.
8. Reconnect the Instagram account through the application.
9. Send one new event; old comments and messages are not replayed automatically.

Do not leave an old app testing the same URL unless the application is intentionally configured to validate signatures from both app secrets.

## 17. Final end-to-end test

Use this order:

1. Start MySQL.
2. Start Laravel on `127.0.0.1:8000`.
3. Start the database queue worker.
4. Start a local tunnel, or use the hosted HTTPS domain.
5. Verify that all Meta URLs use the current public hostname.
6. Use **Verify and save** and confirm the GET verification log.
7. Connect/reconnect the Instagram professional account.
8. Confirm webhook subscription attempts succeed where the app has permission.
9. Create or verify the media resource, template, and active rule.
10. Add a fresh matching comment from a separate accepted tester.
11. Confirm the sequence: signature -> receive -> stored -> processed.
12. Confirm an automation execution and outgoing message were created.
13. If delivery fails, classify the Meta error as token, permission, or capability related.

This sequence separates application defects from Meta configuration and access restrictions.
