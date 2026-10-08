<?php

namespace App\Support;

use App\Models\AutomationRule;
use App\Models\InstagramAccount;
use App\Models\MediaResource;
use App\Models\MessageTemplate;
use App\Models\WebhookEvent;

class SetupGuide
{
    public function summary(): array
    {
        $steps = collect($this->steps());
        $complete = $steps->where('state', 'complete')->count();
        $next = $steps->first(fn (array $step) => $step['state'] !== 'complete');

        return [
            'steps' => $steps->values()->all(),
            'complete' => $complete,
            'total' => $steps->count(),
            'percent' => $steps->count() ? (int) round(($complete / $steps->count()) * 100) : 0,
            'next' => $next,
        ];
    }

    public function steps(): array
    {
        $accountReady = InstagramAccount::where('is_active', true)->whereNotNull('access_token')->exists();
        $resourceReady = MediaResource::where('type', 'media')->where('is_active', true)->exists();
        $templateReady = MessageTemplate::where('is_active', true)->exists();
        $ruleReady = AutomationRule::where('is_active', true)->exists();
        $testReady = WebhookEvent::where('status', 'processed')->exists();

        return [
            $this->step('account', 'Connect Instagram', 'Connect your Professional account so Meta can deliver events.', 'accounts.index', $accountReady, false),
            $this->step('resource', 'Add a Reel or post', 'Choose the Instagram media that should trigger your automation.', 'resources.create', $resourceReady, $accountReady),
            $this->step('template', 'Prepare a message', 'Create or copy a message template for the optional private reply.', 'templates.index', $templateReady, $resourceReady),
            $this->step('rule', 'Create your automation', 'Choose a keyword, response, account, and media item.', 'rules.create', $ruleReady, $templateReady),
            $this->step('test', 'Test and monitor', 'Send a fresh test comment and inspect the result in Activity.', 'activity.index', $testReady, $ruleReady),
        ];
    }

    private function step(string $key, string $label, string $description, string $route, bool $complete, bool $prerequisite): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'url' => route($route),
            'state' => $complete ? 'complete' : ($prerequisite ? 'in_progress' : 'not_started'),
        ];
    }
}
