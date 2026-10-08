<?php

namespace App\Jobs;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Contact;
use App\Models\InstagramAccount;
use App\Models\Interaction;
use App\Models\MediaResource;
use App\Models\OutgoingMessage;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessMetaWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $eventId) {}

    public function handle(): void
    {
        $event = WebhookEvent::findOrFail($this->eventId);
        Log::info('Processing Meta webhook event', [
            'event_id' => $event->id,
            'status' => $event->status,
            'object' => data_get($event->payload, 'object'),
            'entry_count' => count(data_get($event->payload, 'entry', [])),
        ]);

        foreach (data_get($event->payload, 'entry', []) as $entry) {
            if (! $event->instagram_account_id && ($account = InstagramAccount::where('instagram_user_id', (string) data_get($entry, 'id'))->first())) {
                $event->update(['instagram_account_id' => $account->id]);
            }
            if (! $event->instagram_account_id && ($account = InstagramAccount::where('facebook_page_id', (string) data_get($entry, 'id'))->first())) {
                $event->update(['instagram_account_id' => $account->id]);
            }
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = data_get($change, 'value', []);
                $field = data_get($change, 'field');
                Log::info('Meta webhook change received', [
                    'event_id' => $event->id,
                    'entry_id' => data_get($entry, 'id'),
                    'field' => $field,
                ]);
                if (in_array($field, ['comments', 'instagram_comments'], true)) {
                    $this->comment($event, $entry, $value);
                } elseif ($field === 'feed' && data_get($value, 'item') === 'comment') {
                    $this->comment($event, $entry, $this->normalizePageFeedComment($value));
                } if (in_array($field, ['messages', 'messaging'], true)) {
                    $this->message($event, $entry, $value);
                }
            }
        } $event->update(['status' => 'processed', 'processed_at' => now()]);
        Log::info('Meta webhook event processed', ['event_id' => $event->id]);
    }

    private function contact(WebhookEvent $event, array $value): Contact
    {
        $id = (string) (data_get($value, 'from.id') ?? data_get($value, 'sender.id'));

        return Contact::firstOrCreate(['instagram_account_id' => $event->instagram_account_id, 'instagram_scoped_id' => $id], ['username' => data_get($value, 'from.username'), 'name' => data_get($value, 'from.name'), 'last_interacted_at' => now()]);
    }

    private function comment(WebhookEvent $event, array $entry, array $value): void
    {
        $commentId = (string) (data_get($value, 'id') ?? data_get($value, 'comment_id'));
        if (! $commentId) {
            return;
        }

        $account = $event->instagram_account_id
            ? InstagramAccount::find($event->instagram_account_id)
            : null;
        $authorId = (string) (data_get($value, 'from.id') ?? data_get($value, 'sender.id'));
        if ($account && $authorId !== '' && $authorId === (string) $account->instagram_user_id) {
            Log::info('Skipping Instagram comment authored by connected account', [
                'event_id' => $event->id,
                'comment_id' => $commentId,
                'instagram_account_id' => $account->id,
            ]);

            return;
        }

        $mediaId = (string) (data_get($value, 'media.id') ?? data_get($value, 'media_id'));
        $contact = $this->contact($event, $value);
        $media = MediaResource::where('instagram_media_id', $mediaId)->first();
        $interaction = Interaction::firstOrCreate(['source_comment_id' => $commentId], ['contact_id' => $contact->id, 'instagram_account_id' => $event->instagram_account_id, 'media_resource_id' => $media?->id, 'type' => 'comment', 'source_media_id' => $mediaId, 'payload' => $value, 'occurred_at' => now()]);
        $rules = AutomationRule::where('trigger_type', 'comment_keyword')->where('is_active', true)->where(fn ($q) => $q->whereNull('media_resource_id')->orWhere('media_resource_id', $media?->id))->get()->filter(fn ($r) => blank($r->keyword) || Str::contains(Str::lower((string) data_get($value, 'text')), Str::lower($r->keyword)));
        foreach ($rules as $rule) {
            $this->execute($rule, $contact, $media, $interaction, 'comment', $commentId);
        }
    }

    private function normalizePageFeedComment(array $value): array
    {
        $rawMediaId = (string) (data_get($value, 'post_id') ?? data_get($value, 'media_id'));
        $mediaId = str_contains($rawMediaId, '_') ? Str::afterLast($rawMediaId, '_') : $rawMediaId;

        return [
            'id' => (string) (data_get($value, 'comment_id') ?? data_get($value, 'item_id') ?? data_get($value, 'id')),
            'parent_id' => data_get($value, 'parent_id'),
            'media' => ['id' => $mediaId],
            'from' => data_get($value, 'from') ?? data_get($value, 'sender'),
            'text' => data_get($value, 'message') ?? data_get($value, 'text'),
            'page_feed' => $value,
        ];
    }

    private function message(WebhookEvent $event, array $entry, array $value): void
    {
        $messageId = (string) (data_get($value, 'message.mid') ?? data_get($value, 'message.id'));
        if (! $messageId) {
            return;
        } $contact = $this->contact($event, $value);
        $context = Interaction::where('contact_id', $contact->id)->whereNotNull('source_media_id')->latest('occurred_at')->first();
        $interaction = Interaction::firstOrCreate(['source_message_id' => $messageId], ['contact_id' => $contact->id, 'instagram_account_id' => $event->instagram_account_id, 'media_resource_id' => $context?->media_resource_id, 'type' => 'inbound_message', 'source_media_id' => $context?->source_media_id, 'payload' => $value, 'occurred_at' => now()]);
        $rules = AutomationRule::where('trigger_type', 'inbound_message')->where('is_active', true)->where(fn ($q) => $q->whereNull('media_resource_id')->orWhere('media_resource_id', $context?->media_resource_id))->get();
        foreach ($rules as $rule) {
            $this->execute($rule, $contact, $context?->media, $interaction, 'inbound_message', $messageId);
        }
    }

    private function execute(AutomationRule $rule, Contact $contact, ?MediaResource $media, Interaction $interaction, string $origin, string $sourceId): void
    {
        $execution = AutomationExecution::firstOrCreate(['idempotency_key' => "{$origin}:{$sourceId}:rule:{$rule->id}"], ['automation_rule_id' => $rule->id, 'contact_id' => $contact->id, 'media_resource_id' => $media?->id, 'origin' => $origin, 'status' => 'queued', 'context' => ['media_id' => $interaction->source_media_id, 'comment_id' => $interaction->source_comment_id, 'resource_id' => $rule->resource_id]]);
        if (! $execution->wasRecentlyCreated) {
            return;
        } $interaction->update(['automation_rule_id' => $rule->id, 'automation_execution_id' => $execution->id]);
        foreach ([['public_comment_reply', $rule->public_reply, $interaction->source_comment_id, null], ['private_comment_reply', $rule->template?->render(['first_name' => $contact->name ?? $contact->username ?? 'there', 'resource_url' => $rule->resource?->destination_url ?? '']), $interaction->source_comment_id, $rule->message_template_id]] as [$type,$body,$target,$templateId]) {
            if ($body && $target && $origin === 'comment') {
                $out = OutgoingMessage::create(['automation_execution_id' => $execution->id, 'contact_id' => $contact->id, 'message_template_id' => $templateId, 'type' => $type, 'body' => $body, 'target_id' => $target, 'idempotency_key' => "{$execution->id}:{$type}", 'meta' => $execution->context]);
                SendInstagramMessage::dispatch($out->id);
            }
        }
    }
}
