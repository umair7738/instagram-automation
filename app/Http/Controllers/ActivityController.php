<?php

namespace App\Http\Controllers;

use App\Models\AutomationExecution;
use App\Models\OutgoingMessage;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $view = in_array($request->query('view'), ['executions', 'messages', 'webhooks'], true)
            ? $request->query('view')
            : 'executions';

        $status = $request->string('status')->toString();

        $executions = AutomationExecution::query()
            ->with(['rule', 'contact', 'outgoingMessages'])
            ->when($view === 'executions' && $status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20, ['*'], 'executions_page')
            ->withQueryString();

        $messages = OutgoingMessage::query()
            ->with(['execution.rule', 'contact'])
            ->when($view === 'messages' && $status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20, ['*'], 'messages_page')
            ->withQueryString();

        $webhooks = WebhookEvent::query()
            ->with('account')
            ->when($view === 'webhooks' && $status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20, ['*'], 'webhooks_page')
            ->withQueryString();

        return view('activity.index', [
            'view' => $view,
            'status' => $status,
            'executions' => $executions,
            'messages' => $messages,
            'webhooks' => $webhooks,
            'counts' => [
                'executions' => AutomationExecution::count(),
                'messages' => OutgoingMessage::count(),
                'webhooks' => WebhookEvent::count(),
                'failed' => OutgoingMessage::whereIn('status', ['failed', 'blocked'])->count(),
            ],
        ]);
    }
}
