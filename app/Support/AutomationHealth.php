<?php

namespace App\Support;

use App\Models\InstagramAccount;
use App\Models\OutgoingMessage;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutomationHealth
{
    public function summary(): array
    {
        $activeAccount = InstagramAccount::query()
            ->where('is_active', true)
            ->latest()
            ->first();

        $latestWebhook = WebhookEvent::query()->latest()->first();
        $latestDelivery = OutgoingMessage::query()->latest()->first();
        $lastDm = OutgoingMessage::query()
            ->whereIn('type', ['private_reply', 'private_comment_reply', 'dm', 'message'])
            ->latest()
            ->first();

        $queuedJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $oldestJob = $queuedJobs > 0 ? DB::table('jobs')->orderBy('created_at')->first() : null;
        $queueIsStale = $oldestJob && now()->timestamp - (int) $oldestJob->created_at > 300;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        $items = collect([
            $this->item(
                'account',
                'Instagram account',
                $activeAccount && filled($activeAccount->access_token) ? 'ready' : 'action',
                $activeAccount
                    ? '@'.($activeAccount->username ?: $activeAccount->instagram_user_id).' is connected.'
                    : 'Connect an Instagram professional account to begin.',
                route('accounts.index')
            ),
            $this->item(
                'webhook',
                'Webhook delivery',
                $latestWebhook ? 'ready' : 'action',
                $latestWebhook
                    ? 'Last event received '.$latestWebhook->created_at->diffForHumans().'.'
                    : 'No webhook has reached this application yet.',
                route('activity.index', ['view' => 'webhooks'])
            ),
            $this->item(
                'queue',
                'Queue processing',
                $failedJobs > 0 || $queueIsStale ? 'warning' : ($queuedJobs > 0 ? 'working' : 'ready'),
                $failedJobs > 0
                    ? $failedJobs.' failed queue job'.($failedJobs === 1 ? '' : 's').' need attention.'
                    : ($queueIsStale
                        ? $queuedJobs.' job'.($queuedJobs === 1 ? ' has' : 's have').' waited more than five minutes. Check the queue worker.'
                        : ($queuedJobs > 0 ? $queuedJobs.' job'.($queuedJobs === 1 ? '' : 's').' currently waiting.' : 'No failed or waiting queue jobs.')),
                route('activity.index', ['view' => 'messages'])
            ),
            $this->item(
                'delivery',
                'Message delivery',
                $latestDelivery?->status === 'sent' ? 'ready' : ($latestDelivery ? 'warning' : 'pending'),
                $latestDelivery
                    ? 'Latest outgoing action is '.str_replace('_', ' ', $latestDelivery->status).'.'
                    : 'No outgoing action has been created yet.',
                route('activity.index', ['view' => 'messages'])
            ),
            $this->item(
                'dm',
                'Instagram DM capability',
                $lastDm?->status === 'sent' ? 'ready' : 'pending',
                $lastDm?->status === 'sent'
                    ? 'A private message has been delivered successfully.'
                    : 'Not yet proven. Meta must grant messaging capability before live DMs can be delivered.',
                route('activity.index', ['view' => 'messages'])
            ),
        ]);

        return [
            'items' => $items,
            'ready_count' => $items->where('state', 'ready')->count(),
            'total_count' => $items->count(),
            'has_blocker' => $items->contains(fn (array $item) => in_array($item['state'], ['action', 'warning', 'pending'], true)),
        ];
    }

    private function item(string $key, string $label, string $state, string $detail, string $url): array
    {
        return compact('key', 'label', 'state', 'detail', 'url');
    }
}
