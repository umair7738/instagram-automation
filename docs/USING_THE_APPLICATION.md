# Using Instagram Automation

This is the in-app workflow after Meta has been configured and the application is running.

## Start with the Setup guide

Sign in and open **Setup guide** from the navigation. The guide checks the database automatically and shows the next action:

1. Connect Instagram.
2. Add a Reel or post.
3. Prepare a message.
4. Create an automation.
5. Test and monitor.

You can leave the guide at any point. The next time you open it, completed steps remain complete.

## Connect Instagram

Open **Accounts** and choose **Connect with Meta**. Approve the requested permissions and return to the application. The account card should show **Connected** and **Stored securely** for its token.

If the account is missing, reconnect it. Manual account entry is an advanced troubleshooting option; it does not create an access token and is not a replacement for OAuth.

## Add a Reel or post

Open **Media** and choose **Add resource**. Select **Instagram Reel or post**.

The easiest method is **Import from Instagram**:

1. Select the connected Instagram account.
2. Choose **Load recent media**.
3. Select the Reel or post.
4. Check the name and permalink.
5. Save the resource.

The importer fills the numeric Graph media ID for you. If you enter it manually, use digits such as 18019326794001472. Do not enter an Instagram username, shortcode, or public URL in the media ID field.

Use **Destination link** for the URL that should be included in a private message. A Reel/post resource identifies the trigger; a link resource is the destination.

## Create a message template

Open **Templates**. You can create a template from scratch or copy a starter example.

Starter examples are copied as paused drafts. Edit the copy before activating it. The supported variables are:

- **{first_name}** inserts the contact's first name.
- **{resource_url}** inserts the destination URL selected in a rule.

Private message delivery still depends on Meta granting the required messaging capability.

## Create an automation rule

Open **Rules** and choose **Create automation**, or copy a starter example.

Configure the rule in this order:

1. **Rule name**: an internal label, such as INFO comment reply.
2. **Trigger**: choose Comment keyword for comment automations.
3. **Instagram account**: choose the account that should listen.
4. **Reel or post**: choose the media resource that should trigger the rule.
5. **Keyword**: enter the text to detect, such as INFO.
6. **Public comment reply**: the reply visible below the comment.
7. **Private message template**: optional; used for a private reply.
8. **Link resource**: optional; supplies {resource_url}.

Starter rules are paused. Review the account, media, keyword, and responses before activating them.

## Test the automation

Keep the Laravel queue worker running. Use a separate approved Instagram tester account to add a fresh matching comment. Comments made by the connected account are ignored to prevent loops.

Open **Activity** and check the sequence:

1. A webhook delivery was received.
2. An interaction was created.
3. The rule execution was processed.
4. The outgoing public reply or private message was recorded.

If the webhook is received but no execution appears, check that the rule is active, the keyword matches, and the media resource has the correct numeric ID.

## Read the dashboard and Activity page

The Overview page shows active rules, contacts, executions, delivered messages, setup health, and recent webhooks.

Activity separates:

- Webhook deliveries received from Meta
- Rule executions
- Outgoing public replies and private messages

Use the error detail on an outgoing action to distinguish an application problem from a Meta permission or capability restriction.

## Understand DM limitations

The application can receive comments and process rules even when Meta has not approved private messaging. Public comment replies and private replies are separate API operations.

Meta may require Advanced Access, App Review, business verification, and Live mode before DMs work for people outside the approved tester group. Publishing the app does not automatically grant those capabilities. The dashboard marks DM capability as pending until a private message has actually been delivered.

For Meta dashboard setup, callback URLs, permissions, testers, and App Review requirements, read [META_SETUP.md](META_SETUP.md).
