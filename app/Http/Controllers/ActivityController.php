<?php

namespace App\Http\Controllers;

use App\Models\AutomationExecution;
use App\Models\Interaction;
use App\Models\OutgoingMessage;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $view = in_array($request->query('view'), ['comments', 'executions', 'messages', 'webhooks'], true)
            ? $request->query('view')
            : 'comments';

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

        $comments = Interaction::query()
            ->where('type', 'comment')
            ->with(['contact', 'media', 'rule', 'execution.outgoingMessages'])
            ->when($view === 'comments' && in_array($status, ['queued', 'processing', 'sent', 'completed', 'failed', 'blocked'], true), function ($query) use ($status) {
                $query->whereHas('execution.outgoingMessages', fn ($messages) => $messages->where('status', $status));
            })
            ->latest('occurred_at')
            ->latest()
            ->paginate(20, ['*'], 'comments_page')
            ->withQueryString();

        return view('activity.index', [
            'view' => $view,
            'status' => $status,
            'executions' => $executions,
            'messages' => $messages,
            'webhooks' => $webhooks,
            'comments' => $comments,
            'counts' => [
                'comments' => Interaction::where('type', 'comment')->count(),
                'public_replies' => OutgoingMessage::where('type', 'public_comment_reply')->whereIn('status', ['sent', 'completed'])->count(),
                'private_messages' => OutgoingMessage::whereIn('type', ['private_comment_reply', 'direct_message'])->whereIn('status', ['sent', 'completed'])->count(),
                'failed' => OutgoingMessage::whereIn('status', ['failed', 'blocked'])->count(),
            ],
        ]);
    }
}
