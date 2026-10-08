@extends('layouts.app')

@section('title', 'Activity · Instagram Automation')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <div class="eyebrow mb-2">Delivery center</div>
        <h1 class="page-title h2 mb-2">Automation activity</h1>
        <p class="page-subtitle mb-0">See each comment, the public reply, and the private DM as separate delivery results.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('rules.create') }}">Create automation</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="metric-label">Comments received</div><div class="metric-value mt-2">{{ number_format($counts['comments']) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="metric-label">Public replies sent</div><div class="metric-value mt-2">{{ number_format($counts['public_replies']) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="metric-label">Private DMs sent</div><div class="metric-value mt-2">{{ number_format($counts['private_messages']) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100 {{ $counts['failed'] ? 'border-danger-subtle' : '' }}"><div class="card-body"><div class="metric-label">Failed / blocked</div><div class="metric-value mt-2 {{ $counts['failed'] ? 'text-danger' : '' }}">{{ number_format($counts['failed']) }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header bg-white px-3 pt-3 pb-0">
        <ul class="nav nav-tabs border-0" aria-label="Activity type">
            <li class="nav-item"><a class="nav-link {{ $view === 'comments' ? 'active' : '' }}" href="{{ route('activity.index', ['view' => 'comments']) }}">Comments</a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'executions' ? 'active' : '' }}" href="{{ route('activity.index', ['view' => 'executions']) }}">Executions</a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'messages' ? 'active' : '' }}" href="{{ route('activity.index', ['view' => 'messages']) }}">Delivery details <span class="visually-hidden">Outgoing actions</span></a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'webhooks' ? 'active' : '' }}" href="{{ route('activity.index', ['view' => 'webhooks']) }}">Webhooks</a></li>
        </ul>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small fw-semibold" for="status-filter">Status</label>
                <select class="form-select" id="status-filter" name="status">
                    <option value="">All statuses</option>
                    @foreach(['received','processed','queued','processing','sent','completed','failed','blocked'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary">Apply filter</button></div>
            @if($status)<div class="col-auto"><a class="btn btn-link text-decoration-none" href="{{ route('activity.index', ['view' => $view]) }}">Clear</a></div>@endif
        </form>
    </div>

    @if($view === 'comments')
        <div class="alert alert-light border-0 rounded-0 border-bottom mb-0 small">
            Each row is one incoming Instagram comment. Public replies are visible under the comment; private replies are tracked as DMs. A failed DM does not hide a successful public reply.
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Commenter</th><th>Comment</th><th>Reel / post</th><th>Public reply</th><th>Private DM</th><th>Received</th></tr></thead>
                <tbody>
                @forelse($comments as $comment)
                    @php
                        $commentMessages = $comment->execution?->outgoingMessages ?? collect();
                        $publicReply = $commentMessages->firstWhere('type', 'public_comment_reply');
                        $privateReply = $commentMessages->first(fn ($message) => in_array($message->type, ['private_comment_reply', 'direct_message'], true));
                        $commentText = data_get($comment->payload, 'text') ?? data_get($comment->payload, 'message') ?? 'Comment text not captured';
                        $statusTone = fn ($message) => match ($message?->status) { 'sent', 'completed' => 'ready', 'queued', 'processing' => 'working', 'failed', 'blocked' => 'warning', default => 'neutral' };
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $comment->contact?->username ? '@'.$comment->contact->username : ($comment->contact?->name ?? 'Unknown commenter') }}</div>
                            <div class="small text-muted">{{ $comment->contact?->instagram_scoped_id ?? data_get($comment->payload, 'from.id') ?? 'No user ID' }}</div>
                        </td>
                        <td style="min-width:240px; max-width:360px">
                            <div>{{ \Illuminate\Support\Str::limit($commentText, 140) }}</div>
                            <div class="small text-muted">Comment #{{ $comment->source_comment_id ?? $comment->id }}</div>
                        </td>
                        <td>
                            @if($comment->media?->permalink)
                                <a href="{{ $comment->media->permalink }}" target="_blank" rel="noopener">{{ $comment->media->name }}</a>
                            @else
                                <span>{{ $comment->media?->name ?? $comment->source_media_id ?? 'Unknown media' }}</span>
                            @endif
                            @if($comment->rule)<div class="small text-muted">Rule: {{ $comment->rule->name }}</div>@endif
                        </td>
                        <td>
                            @if($publicReply)
                                <span class="badge-status status-{{ $statusTone($publicReply) }}">{{ str_replace('_', ' ', $publicReply->status) }}</span>
                                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($publicReply->body, 70) }}</div>
                                @if($publicReply->status === 'failed')<div class="small text-danger mt-1">Not delivered</div>@endif
                            @else
                                <span class="small text-muted">Not configured</span>
                            @endif
                        </td>
                        <td>
                            @if($privateReply)
                                <span class="badge-status status-{{ $statusTone($privateReply) }}">{{ str_replace('_', ' ', $privateReply->status) }}</span>
                                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($privateReply->body, 70) }}</div>
                                @if($privateReply->status === 'failed')<div class="small text-danger mt-1">Check Meta DM permissions</div>@endif
                            @else
                                <span class="small text-muted">Not configured</span>
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap">{{ ($comment->occurred_at ?? $comment->created_at)?->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><strong>No Instagram comments found</strong><div class="mt-1">Add a comment to a media item that has an active rule, then refresh this page.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $comments->links() }}</div>
    @elseif($view === 'executions')
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Rule and source</th><th>Contact</th><th>Status</th><th>Outgoing actions</th><th>Started</th></tr></thead>
                <tbody>
                @forelse($executions as $execution)
                    @php $tone = match($execution->status) { 'sent','processed','completed' => 'ready', 'queued','processing' => 'working', 'failed','blocked' => 'warning', default => 'neutral' }; @endphp
                    <tr>
                        <td><div class="fw-semibold">{{ $execution->rule?->name ?? 'Deleted rule' }}</div><div class="small text-muted text-capitalize">{{ str_replace('_', ' ', $execution->origin) }} · #{{ $execution->id }}</div></td>
                        <td><div>{{ $execution->contact?->username ? '@'.$execution->contact->username : ($execution->contact?->name ?? 'Unknown') }}</div><div class="small text-muted">{{ $execution->contact?->instagram_scoped_id }}</div></td>
                        <td><span class="badge-status status-{{ $tone }}">{{ str_replace('_', ' ', $execution->status) }}</span></td>
                        <td>
                            @forelse($execution->outgoingMessages as $message)
                                <div class="small"><span class="text-capitalize">{{ str_replace('_', ' ', $message->type) }}</span> <span class="text-muted">· {{ $message->status }}</span></div>
                            @empty<span class="small text-muted">No outgoing action</span>@endforelse
                        </td>
                        <td class="small text-muted text-nowrap">{{ $execution->created_at->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><strong>No executions found</strong><div class="mt-1">Try another filter or create an active rule.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $executions->links() }}</div>
    @elseif($view === 'messages')
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Action</th><th>Rule / contact</th><th>Status</th><th>Details</th><th>Created</th></tr></thead>
                <tbody>
                @forelse($messages as $message)
                    @php
                        $tone = match($message->status) { 'sent','completed' => 'ready', 'queued','processing' => 'working', 'failed','blocked' => 'warning', default => 'neutral' };
                        $error = data_get($message->meta, 'error.message') ?? data_get($message->meta, 'error.body');
                    @endphp
                    <tr>
                        <td><div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $message->type) }}</div><div class="small text-muted">Action #{{ $message->id }}</div></td>
                        <td><div>{{ $message->execution?->rule?->name ?? 'Deleted rule' }}</div><div class="small text-muted">{{ $message->contact?->username ? '@'.$message->contact->username : $message->target_id }}</div></td>
                        <td><span class="badge-status status-{{ $tone }}">{{ $message->status }}</span></td>
                        <td style="min-width:260px">
                            @if($error)
                                <details><summary class="small text-danger fw-semibold">View failure reason</summary><div class="activity-detail code-break small mt-2">{{ $error }}</div></details>
                            @elseif($message->status === 'sent')
                                <span class="small text-muted">Delivered {{ $message->sent_at?->diffForHumans() }}</span>
                            @else
                                <span class="small text-muted">{{ \Illuminate\Support\Str::limit($message->body, 80) }}</span>
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap">{{ $message->created_at->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><strong>No outgoing actions found</strong><div class="mt-1">Messages and public replies will appear after a rule matches.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $messages->links() }}</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Event</th><th>Account</th><th>Status</th><th>Payload summary</th><th>Received</th></tr></thead>
                <tbody>
                @forelse($webhooks as $event)
                    @php
                        $field = data_get($event->payload, 'entry.0.changes.0.field') ?? (data_get($event->payload, 'entry.0.messaging.0.message') ? 'message' : data_get($event->payload, 'object', 'unknown'));
                        $tone = in_array($event->status, ['received','processed'], true) ? 'ready' : ($event->status === 'failed' ? 'warning' : 'working');
                    @endphp
                    <tr>
                        <td><div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $field) }}</div><div class="small text-muted">Webhook #{{ $event->id }}</div></td>
                        <td>{{ $event->account?->username ? '@'.$event->account->username : 'Unmatched' }}</td>
                        <td><span class="badge-status status-{{ $tone }}">{{ $event->status }}</span></td>
                        <td style="min-width:260px"><details><summary class="small fw-semibold">Inspect event</summary><div class="activity-detail code-break small mt-2">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div></details></td>
                        <td class="small text-muted text-nowrap">{{ $event->created_at->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><strong>No webhook events found</strong><div class="mt-1">Check the callback URL and Meta subscription.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $webhooks->links() }}</div>
    @endif
</div>
@endsection
