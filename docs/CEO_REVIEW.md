# CEO review guide

Use this guide when demonstrating the project as a product MVP.

## What the demo proves

- A connected Instagram professional account can receive Meta webhook events.
- Incoming comments are stored and matched against active automation rules.
- Public comment replies can be queued and delivered through the Graph API.
- Every webhook, rule execution, outgoing action, delivery result, and Meta error is visible in the application.
- Rules, message templates, media targets, and destination links can be managed without editing code.

## Current external dependency

Private Instagram messages require Meta to grant the application the relevant messaging capability. The application can configure, queue, and trace a private reply, but a production DM must not be described as working until the Activity screen shows a private action with the `sent` status.

This limitation is displayed in the dashboard health panel and automation builder.

## Pre-demo checklist

1. Deploy the application to a stable HTTPS domain. A temporary Cloudflare or ngrok URL is suitable only for local development.
2. Run database migrations.
3. Keep the Laravel queue worker running.
4. Configure the final HTTPS OAuth redirect and webhook callback URLs in Meta.
5. Connect the Instagram account from the Accounts page.
6. Confirm the dashboard reports the account and webhook checks as ready.
7. Create or verify a media resource using the numeric Graph API media ID.
8. Create an active comment rule with a public reply.
9. Add a new comment from a different Instagram account.
10. Confirm the webhook, execution, and outgoing action appear in Activity.

## Recommended demonstration path

1. Start on **Overview** and explain the system health checks.
2. Open **Rules** and show the active automation.
3. Open the rule builder to demonstrate trigger-specific fields and the private-message preview.
4. Add a real Instagram comment that matches the rule.
5. Return to **Activity** and trace the event through Webhooks, Executions, and Outgoing actions.
6. Show the public reply on Instagram.
7. If DM permission is still pending, open the private action failure reason and explain that Meta access is the remaining external dependency.

## Approval language

Describe the application as a working comment-automation MVP with transparent delivery diagnostics. Describe DM automation as ready in the codebase but pending Meta capability until a private action is visibly marked `sent`.
