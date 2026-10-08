<?php

namespace App\Http\Controllers;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Contact;
use App\Models\OutgoingMessage;
use App\Models\WebhookEvent;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', ['rules' => AutomationRule::count(), 'contacts' => Contact::count(), 'executions' => AutomationExecution::count(), 'queued' => OutgoingMessage::where('status', 'queued')->count(), 'events' => WebhookEvent::latest()->take(8)->get()]);
    }
}
