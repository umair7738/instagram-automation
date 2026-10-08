# Meta setup for a first-time user

This guide configures the Meta side of the Instagram Automation application. It is written for someone who can use a web dashboard but does not want to edit PHP code.

The application has two separate Meta login options. Pick one and keep its credentials and permissions together:

| Option | Use it when | What it needs |
| --- | --- | --- |
| **Connect with Meta** | You have an Instagram Professional account connected to a Facebook Page. This is the default button in the app. | Facebook Login for Business, a Page connection, and the `instagram_*` permissions. |
| **Connect with Instagram Login** | You have an app configured for Instagram Login and want the Instagram API login flow. | A separate Instagram Login app, the `instagram_business_*` permissions, and the extra Instagram credentials in `.env`. |

Do not combine the two options. The default application button uses the first option. The second button appears only when `META_INSTAGRAM_APP_ID` and `META_INSTAGRAM_APP_SECRET` have been configured.

### The shortest path for the default setup

If you only want to use the blue **Connect with Meta** button, follow sections 1 through 8 in order. Leave the optional `META_INSTAGRAM_*` values empty. Use section 9 after the account appears in the application. Read section 10 before promising private DMs, because Meta may require approval that the code cannot grant.

## Permission checklist

The permission names depend on the login option. Do not replace one family with the other.

### Default: Connect with Meta (Facebook Login for Business)

| Permission | Why this application requests it | Needed for media import? |
| --- | --- | --- |
| instagram_basic | Reads the Professional Instagram account profile and its media list. | Yes |
| pages_show_list | Finds the Facebook Pages managed by the person connecting the account. | Needed for the Page connection step |
| pages_read_engagement | Reads the Page's connected Instagram Professional account details. | Needed for the Page connection step |
| pages_manage_metadata | Allows the app to manage the Page webhook subscription used for feed/comment events. | No |
| instagram_manage_comments | Receives and handles Instagram comment events and public comment replies. | No |
| instagram_manage_messages | Enables private comment replies and Instagram DMs when Meta grants access. | No; required for messaging |
| business_management | Used by the Facebook Login for Business setup when Meta requires business asset selection. | No |

For selecting Reels or posts, the important permission is instagram_basic, together with the Page discovery permissions needed by this Facebook Login flow. Adding messaging permissions will not fix a media-import error.

### Optional: Connect with Instagram Login

| Permission | Why it is used |
| --- | --- |
| instagram_business_basic | Reads the Instagram Professional account profile and media list. |
| instagram_business_manage_comments | Receives and handles comment events and public comment replies. |
| instagram_business_manage_messages | Enables private replies and DMs when Meta grants access. |

Instagram Login does not use the Page permissions or business_management for this application flow. Meta's current Instagram Login documentation uses the instagram_business_* names. See [Instagram API with Instagram Login](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login) and [Instagram API with Facebook Login](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login).

### Where to check them in Meta

Open the Meta app and go to **Use cases → Customize** or **App Review → Permissions and features**. The exact labels vary by Meta app type. Confirm that the permissions for the selected login option are present and that the app role/tester has granted them.

An app in Development mode can use permissions for approved app roles and accepted testers. Production users may require Advanced Access, App Review, business verification, and Live mode. A permission appearing in the dashboard does not mean Meta has granted production access.

After changing permissions, reconnect the account in the application. Existing access tokens do not gain newly added permissions automatically.

## What you need before you start

Have these ready:

- A Meta developer account at <https://developers.facebook.com/>.
- An Instagram Professional account (Business or Creator).
- A Facebook Page connected to that Instagram account if you use **Connect with Meta**.
- Access to the application's `.env` file.
- A public HTTPS address for the running application.

The public address can be a real hosting domain or a temporary local tunnel such as Cloudflare Tunnel or ngrok. A tunnel is only a way to let Meta reach a laptop. It is not required after the application is deployed on a server with HTTPS.

Write down the public host without a trailing slash. In the examples below, replace `PUBLIC-HOST` with that host:

```text
https://PUBLIC-HOST
```

There are two different callback paths. Copy them exactly:

```text
OAuth login: https://PUBLIC-HOST/meta/callback
Webhook:     https://PUBLIC-HOST/webhooks/meta
```

`/webhook/meta` is wrong. The route contains the plural word `webhooks`.

## 1. Start the application first

The Meta dashboard cannot verify a stopped local application.

Run these commands in separate terminals from the project folder:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
php artisan queue:work database --sleep=1 --tries=3 --timeout=90
```

If you are developing locally, start one tunnel that forwards to `http://127.0.0.1:8000`, for example:

```powershell
cloudflared tunnel --url http://127.0.0.1:8000
```

or:

```powershell
ngrok http 8000
```

Keep the Laravel server, queue worker, and tunnel running while testing. If the tunnel gives you a new hostname, update every Meta URL and `META_OAUTH_REDIRECT_URI` before reconnecting.

## 2. Create or select the Meta app

1. Open **Meta for Developers** and choose **My Apps**.
2. Select the app you want to use, or choose **Create App**.
3. Use the Instagram messaging/content use case offered by Meta. The exact wording can change; choose the use case that provides Instagram API, login, and webhooks.
4. Open **App settings → Basic** and copy the **App ID** into `META_APP_ID`.
5. Click **Show** beside **App secret** and copy it into `META_APP_SECRET`.
6. Set a contact email, app category, and app icon if Meta asks for them.

Never use the app secret as the webhook verify token. The verify token is a separate private string chosen by you.

In the application `.env`, the default flow should look like this (use your own values):

```dotenv
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_WEBHOOK_VERIFY_TOKEN=choose_a_private_word_or_phrase
META_OAUTH_REDIRECT_URI=https://PUBLIC-HOST/meta/callback
```

After changing `.env`, run:

```powershell
php artisan optimize:clear
```

Then restart Laravel and the queue worker.

## 3. Add the login redirect URL

In the Meta app:

1. Open the Facebook Login for Business product. If it is not listed, add it from **Add product**.
2. Open its **Settings**.
3. Find **Valid OAuth Redirect URIs**.
4. Add this full URL, including the path:

   ```text
   https://PUBLIC-HOST/meta/callback
   ```

5. Save the changes.

Then open **App settings → Basic** and add only the hostname under **App domains**:

```text
PUBLIC-HOST
```

Do not enter `https://`, `/meta/callback`, or `/webhooks/meta` in **App domains**.

## 4. Add the legal URLs

Meta may block publishing until these fields are filled. The application already provides these pages:

```text
https://PUBLIC-HOST/privacy-policy
https://PUBLIC-HOST/data-deletion
https://PUBLIC-HOST/terms-of-service
```

In **App settings → Basic**, enter the privacy policy URL. In the data deletion setting, choose the URL option and enter the data deletion URL. Add the terms URL wherever Meta shows a Terms of Service field.

These pages must be reachable in a private browser window without signing in.

## 5. Configure the webhook

1. In the Meta app, open **Use cases** or **Webhooks**.
2. Open the Instagram/Page webhook configuration for the product you selected.
3. Enter this **Callback URL**:

   ```text
   https://PUBLIC-HOST/webhooks/meta
   ```

4. Enter the exact value after `META_WEBHOOK_VERIFY_TOKEN=` in `.env` as the **Verify token**.
5. Click **Verify and save**.
6. Subscribe to the Instagram comment field shown by Meta. Depending on the product, it may be called `comments` or the Page feed field `feed`.
7. Subscribe to messages only if Meta has granted the messaging capability for this app. A visible field is not the same as permission to use it in production.

The same callback URL handles both methods on purpose:

- `GET /webhooks/meta` is the one-time verification request.
- `POST /webhooks/meta` is an event delivery.

Laravel chooses the correct handler based on the HTTP method.

## 6. Test the webhook correctly

Do not paste the callback URL into a browser and use that as a test. A normal browser request has no Meta verification parameters or signed event body.

Use this order:

1. Click **Verify and save** in Meta. This confirms the URL and verify token.
2. Open one subscribed field, such as comments or feed.
3. Click **Test**.
4. In the sample dialog, click **Send to server**.
5. Watch `storage/logs/laravel.log` or the application's Activity page.

Verification alone does not create a database event row. A POST from **Send to server** or a real Instagram action does.

The expected application sequence is:

```text
Meta webhook signature validated
Meta webhook request received
Meta webhook event stored
Meta webhook processing job dispatched
Meta webhook event processed
```

Meta's sample event may be accepted and stored even though it cannot match your real Instagram media, account, or keyword rule. Use a real comment for the end-to-end automation test.

## 7. Add testers when the app is in Development mode

Development mode limits who can use the app and who can create production events.

1. Open **App roles**, **Testers**, or the Instagram tester area in Meta. The label varies by app type.
2. Invite each Instagram account that will test the integration.
3. Sign in to Instagram as that account and accept the invitation under the account's tester invitations/apps area.
4. Use that accepted tester account to comment on or message the connected professional account.

Adding a tester is not enough by itself. The invitation must be accepted, and the account used for the test must be the account that accepted it.

## 8. Connect the Instagram account in the application

1. Open the application's **Accounts** page.
2. Click **Connect with Meta** for the default flow.
3. Sign in to the Facebook account that manages the connected Page.
4. Select the Page connected to the Instagram Professional account.
5. Approve all requested permissions.
6. Return to the application and confirm that the Instagram account is listed as connected.

The application then attempts to subscribe the Instagram account to `comments` and `messages`, and the Page to `feed`. A messaging subscription can fail while comment subscription succeeds if Meta has not granted messaging capability.

## 9. Create the first automation

1. Open **Media & resources** and add the Reel or post.
2. Enter the numeric Graph API media ID. Do not enter the Instagram username, shortcode, or permalink as the ID.
3. Create a message template if you want to send a resource link.
4. Open **Rules** and create an active comment rule.
5. Select the connected account, media, keyword, and public reply.
6. Add a new matching comment from the accepted tester account.
7. Confirm the webhook, interaction, execution, and outgoing message in **Activity**.

The queue worker must still be running. Comments authored by the connected Instagram account are ignored to prevent reply loops.

## 10. What works without App Review or business verification?

Meta controls access to Instagram messaging. You can often demonstrate comment webhooks, rule matching, database storage, and public comment replies with approved testers in Development mode.

Private comment replies and DMs may require Meta's Advanced Access, App Review, business verification, and Live mode. Publishing the app does not automatically grant those capabilities. No Laravel code can bypass a Meta capability error.

If private messages are the deliverable, show the exact Meta permission error and the successful webhook/comment pipeline separately. That is an external access blocker, not proof that the webhook code is broken.

## Quick checks when something fails

| What you see | Check this first |
| --- | --- |
| Meta cannot verify | The callback is `/webhooks/meta`, the tunnel is running, and the verify token matches `.env` exactly. |
| Browser shows 403 | That was a normal browser GET; use Meta's **Verify and save** action. |
| Laravel server shows no request | The public host is wrong, the tunnel is stopped, or it forwards to the wrong port. |
| Request is logged but no automation runs | Start the queue worker and check the active rule, keyword, account, and numeric media ID. |
| Comment webhook works but DM fails | Check Meta messaging permission/access; this is not fixed by changing the URL. |
| OAuth says redirect URL is invalid | The Meta redirect URI and `.env` value must be identical, character for character. |
| Tunnel hostname changed | Update `.env`, OAuth redirect URI, webhook URL, and app domain, then reconnect. |

For deeper diagnostics, continue with [TROUBLESHOOTING.md](TROUBLESHOOTING.md).
