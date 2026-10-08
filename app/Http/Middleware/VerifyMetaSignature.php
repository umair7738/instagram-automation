<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $secrets = config('meta.webhook_secrets', []);
        if (! is_array($secrets) || $secrets === []) {
            Log::warning('Meta webhook signature validation skipped because app secret is not configured');

            return $next($request);
        }

        $provided = (string) $request->header('X-Hub-Signature-256');
        file_put_contents(
            storage_path('logs/webhook-debug.log'),
            now()->toIso8601String()." signature_check path={$request->path()} signature_present=".(filled($provided) ? 'yes' : 'no')." content_length=".strlen($request->getContent()).PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
        $valid = false;
        foreach ($secrets as $secret) {
            $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), (string) $secret);
            if (hash_equals($expected, $provided)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            Log::warning('Meta webhook rejected because signature validation failed', [
                'signature_present' => filled($provided),
                'content_length' => strlen($request->getContent()),
            ]);
            abort(403, 'Invalid Meta signature.');
        }

        Log::info('Meta webhook signature validated');

        return $next($request);
    }
}
