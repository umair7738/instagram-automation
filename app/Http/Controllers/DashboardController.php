<?php

namespace App\Http\Controllers;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\OutgoingMessage;
use App\Models\WebhookEvent;
use App\Support\AutomationHealth;
use App\Support\SetupGuide;

class DashboardController extends Controller
{
    public function __invoke(AutomationHealth $health, SetupGuide $setupGuide)
    {
        $messageCounts = OutgoingMessage::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard', [
            'rules' => AutomationRule::count(),
            'activeRules' => AutomationRule::where('is_active', true)->count(),
            'contacts' => Contact::count(),
            'executions' => AutomationExecution::count(),
            'interactions' => Interaction::count(),
            'queued' => (int) ($messageCounts['queued'] ?? 0),
            'sent' => (int) ($messageCounts['sent'] ?? 0),
            'failed' => (int) (($messageCounts['failed'] ?? 0) + ($messageCounts['blocked'] ?? 0)),
            'events' => WebhookEvent::with('account')->latest()->take(6)->get(),
            'recentExecutions' => AutomationExecution::with(['rule', 'contact', 'outgoingMessages'])->latest()->take(6)->get(),
            'health' => $health->summary(),
            'setup' => $setupGuide->summary(),
        ]);
    }
}
