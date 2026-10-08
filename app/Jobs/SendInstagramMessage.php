<?php

namespace App\Jobs;

use App\Models\OutgoingMessage;
use App\Services\Automation\MessagingPolicy;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInstagramMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $outgoingMessageId) {}

    /**
     * Execute the job.
     */
    public function handle(MessagingPolicy $policy, MetaGraphClient $client): void
    {
        $message = OutgoingMessage::findOrFail($this->outgoingMessageId);
        if ($message->status !== 'queued' || ! $policy->permits($message)) {
            return;
        }

        $account = $message->execution->rule->account;
        if (! $account?->access_token) {
            $message->update(['status' => 'blocked']);

            return;
        }

        if ($message->contact?->instagram_scoped_id === (string) $account->instagram_user_id) {
            $message->update(['status' => 'blocked']);

            return;
        }

        try {
            $response = $client->send($message, $account);
            $message->update([
                'status' => 'sent',
                'external_message_id' => data_get($response, 'message_id') ?? data_get($response, 'id'),
                'sent_at' => now(),
                'meta' => array_merge($message->meta ?? [], ['response' => $response]),
            ]);
        } catch (\Throwable $exception) {
            $error = [
                'message' => $exception->getMessage(),
            ];

            if (method_exists($exception, 'response') && $exception->response()) {
                $error['status'] = $exception->response()->status();
                $error['body'] = substr($exception->response()->body(), 0, 1000);
            }

            \Illuminate\Support\Facades\Log::error('Instagram message send failed', [
                'outgoing_message_id' => $message->id,
                'type' => $message->type,
                'target_id' => $message->target_id,
                'error' => $error,
            ]);

            $message->update([
                'status' => 'failed',
                'meta' => array_merge($message->meta ?? [], ['error' => $error]),
            ]);
        }
    }
}
