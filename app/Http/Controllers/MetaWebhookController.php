<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMetaWebhook;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class MetaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        return $request->isMethod('GET')
            ? $this->verify($request)
            : $this->receive($request);
    }

    private function verify(Request $request)
    {
        file_put_contents(
            storage_path('logs/webhook-debug.log'),
            now()->toIso8601String()." verify method={$request->method()} path={$request->path()}".PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
        Log::info('Meta webhook verification requested', [
            'mode' => $request->query('hub_mode'),
            'has_token' => filled($request->query('hub_verify_token')),
            'has_challenge' => filled($request->query('hub_challenge')),
        ]);

        abort_unless(hash_equals((string) config('meta.verify_token'), (string) $request->query('hub_verify_token')) && $request->query('hub_mode') === 'subscribe', 403);

        return response($request->query('hub_challenge'), 200);
    }

    private function receive(Request $request)
    {
        $payload = $request->all();
        $key = hash('sha256', $request->getContent());
        file_put_contents(
            storage_path('logs/webhook-debug.log'),
            now()->toIso8601String()." receive path={$request->path()} object=".($payload['object'] ?? 'none')." content_hash={$key}".PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
        Log::info('Meta webhook request received', [
            'object' => $payload['object'] ?? null,
            'entry_count' => is_array($payload['entry'] ?? null) ? count($payload['entry']) : 0,
            'content_hash' => $key,
            'signature_present' => filled($request->header('X-Hub-Signature-256')),
        ]);

        $event = WebhookEvent::firstOrCreate(['event_key' => $key], ['payload' => $payload, 'status' => 'received']);
        Log::info('Meta webhook event stored', [
            'event_id' => $event->id,
            'created' => $event->wasRecentlyCreated,
            'status' => $event->status,
        ]);

        if ($event->wasRecentlyCreated) {
            ProcessMetaWebhook::dispatch($event->id);
            Log::info('Meta webhook processing job dispatched', ['event_id' => $event->id]);
        }

        return response()->json(['received' => true], 200);
    }
}
