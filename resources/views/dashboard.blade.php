@extends('layouts.app')

@section('title', 'Overview · Instagram Automation')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4" data-tour-target="overview">
    <div>
        <div class="eyebrow mb-2">Workspace overview</div>
        <h1 class="page-title h2 mb-2">Your automation at a glance</h1>
        <p class="page-subtitle mb-0">Monitor incoming Instagram events, rule matches and message delivery from one place.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('setup.index') }}">Setup guide</a>
        <button class="btn btn-outline-secondary" type="button" data-tour-start>Take a tour</button>
        <a class="btn btn-outline-secondary" href="{{ route('activity.index') }}">View activity</a>
        <a class="btn btn-primary" href="{{ route('rules.create') }}">Create automation</a>
    </div>
</div>

@php
    $metrics = [
        ['label' => 'Active rules', 'value' => $activeRules, 'detail' => $rules.' total', 'route' => route('rules.index'), 'icon' => 'R'],
        ['label' => 'Contacts', 'value' => $contacts, 'detail' => $interactions.' interactions', 'route' => route('activity.index', ['view' => 'executions']), 'icon' => 'C'],
        ['label' => 'Executions', 'value' => $executions, 'detail' => 'Matched automations', 'route' => route('activity.index', ['view' => 'executions']), 'icon' => 'E'],
        ['label' => 'Delivered', 'value' => $sent, 'detail' => $failed.' failed · '.$queued.' queued', 'route' => route('activity.index', ['view' => 'messages']), 'icon' => 'D'],
    ];
@endphp

<div class="row g-3 mb-4" id="overview-metrics" data-tour-target="metrics">
    @foreach($metrics as $metric)
        <div class="col-6 col-xl-3">
            <a class="card metric-card h-100" href="{{ $metric['route'] }}">
                <div class="card-body d-flex justify-content-between gap-3">
                    <div>
                        <div class="metric-label mb-2">{{ $metric['label'] }}</div>
                        <div class="metric-value">{{ number_format($metric['value']) }}</div>
                        <div class="small text-muted mt-2">{{ $metric['detail'] }}</div>
                    </div>
                    <div class="icon-tile" aria-hidden="true">{{ $metric['icon'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <div>
                    <h2 class="h5 mb-1">Recent automation runs</h2>
                    <div class="small text-muted">The outcome of each matched rule</div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('activity.index', ['view' => 'executions']) }}">See all</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Rule</th><th>Contact</th><th>Outcome</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($recentExecutions as $execution)
                        @php
                            $message = $execution->outgoingMessages->sortByDesc('id')->first();
                            $outcome = $message?->status ?? $execution->status;
                            $tone = match($outcome) { 'sent', 'processed', 'completed' => 'ready', 'queued', 'processing' => 'working', 'failed', 'blocked' => 'warning', default => 'neutral' };
                        @endphp
                        <tr>
                            <td><div class="fw-semibold">{{ $execution->rule?->name ?? 'Deleted rule' }}</div><div class="small text-muted">{{ str_replace('_', ' ', $execution->origin) }}</div></td>
                            <td>{{ $execution->contact?->username ? '@'.$execution->contact->username : ($execution->contact?->name ?? 'Unknown') }}</td>
                            <td><span class="badge-status status-{{ $tone }}">{{ str_replace('_', ' ', $outcome) }}</span></td>
                            <td class="small text-muted text-nowrap">{{ $execution->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state"><strong>No executions yet</strong><div class="mt-1">New rule matches will appear here.</div></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5" data-tour-target="health">
        <div class="card h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <div>
                    <h2 class="h5 mb-1">Setup and system health</h2>
                    <div class="small text-muted">{{ $health['ready_count'] }} of {{ $health['total_count'] }} checks ready</div>
                </div>
                <span class="badge-status {{ $health['has_blocker'] ? 'status-warning' : 'status-ready' }}">{{ $health['has_blocker'] ? 'Needs attention' : 'Ready' }}</span>
            </div>
            <div class="card-body py-1">
                @foreach($health['items'] as $item)
                    <a class="setup-item text-decoration-none text-reset" href="{{ $item['url'] }}">
                        <span class="setup-icon status-{{ $item['state'] }}" aria-hidden="true">{{ $item['state'] === 'ready' ? '✓' : ($item['state'] === 'working' ? '↻' : '!') }}</span>
                        <span class="flex-grow-1">
                            <span class="d-flex align-items-center justify-content-between gap-2">
                                <strong>{{ $item['label'] }}</strong><span class="small text-muted">View</span>
                            </span>
                            <span class="small text-muted d-block mt-1">{{ $item['detail'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card mt-4" data-tour-target="activity">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <div>
            <h2 class="h5 mb-1">Recent webhook deliveries</h2>
            <div class="small text-muted">Requests accepted from Meta</div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('activity.index', ['view' => 'webhooks']) }}">Inspect webhooks</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Event</th><th>Account</th><th>Status</th><th>Received</th></tr></thead>
            <tbody>
            @forelse($events as $event)
                @php
                    $field = data_get($event->payload, 'entry.0.changes.0.field') ?? (data_get($event->payload, 'entry.0.messaging.0.message') ? 'message' : data_get($event->payload, 'object', 'unknown'));
                    $tone = in_array($event->status, ['processed', 'received'], true) ? 'ready' : ($event->status === 'failed' ? 'warning' : 'working');
                @endphp
                <tr>
                    <td><span class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $field) }}</span><div class="small text-muted">Webhook #{{ $event->id }}</div></td>
                    <td>{{ $event->account?->username ? '@'.$event->account->username : 'Unmatched' }}</td>
                    <td><span class="badge-status status-{{ $tone }}">{{ $event->status }}</span></td>
                    <td class="text-nowrap small text-muted">{{ $event->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty-state"><strong>No webhook deliveries</strong><div class="mt-1">Complete Meta webhook setup to receive events.</div></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
