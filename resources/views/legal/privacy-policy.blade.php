<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy | Instagram Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 900px">
    <article class="bg-white rounded-3 shadow-sm p-4 p-md-5">
        <h1 class="h2 mb-3">Privacy Policy</h1>
        <p class="text-secondary">Last updated: {{ now()->toFormattedDateString() }}</p>

        <p>This application helps an Instagram Professional account manage comments and direct messages using Meta’s APIs.</p>

        <h2 class="h5 mt-4">Information we process</h2>
        <p>When you connect an Instagram Professional account, the application may process account identifiers, media identifiers, comment and message identifiers, message text, and automation configuration needed to provide the service.</p>

        <h2 class="h5 mt-4">How information is used</h2>
        <p>We use this information only to receive webhook events, apply configured automation rules, send permitted replies, prevent duplicate processing, and maintain delivery logs.</p>

        <h2 class="h5 mt-4">Sharing and retention</h2>
        <p>Information is sent to Meta only through the Meta APIs required for the requested Instagram actions. Application records are stored in the application database and retained only as long as needed to operate, troubleshoot, and secure the service.</p>

        <h2 class="h5 mt-4">Security</h2>
        <p>Access tokens are stored using application encryption and are not displayed in the application interface or logs. Webhook requests are checked using Meta’s signature.</p>

        <h2 class="h5 mt-4">Your choices</h2>
        <p>You may disconnect the Instagram account, disable automation rules, or request deletion of stored account-related records from the application owner. For privacy requests, contact the app owner through the email associated with this Meta application.</p>

        <h2 class="h5 mt-4">Changes</h2>
        <p>This policy may be updated when the application’s data practices change. The latest version will remain available at this URL.</p>

        <p class="mt-4 mb-0"><a href="{{ route('data-deletion') }}">Data deletion instructions</a></p>
    </article>
</main>
</body>
</html>
