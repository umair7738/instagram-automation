@extends('layouts.app')
@section('title', 'Rules · Instagram Automation')
@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4">
    <div><div class="eyebrow mb-2">Automation builder</div><h1 class="page-title h2 mb-2">Automation rules</h1><p class="page-subtitle mb-0">Choose what triggers an automation and what Instagram should receive in response.</p></div>
    <a class="btn btn-primary" href="{{ route('rules.create') }}">Create automation</a>
</div>
<div class="card border-primary-subtle bg-primary-subtle mb-4"><div class="card-body p-4"><div class="d-flex flex-column flex-lg-row justify-content-between gap-3"><div><h2 class="h5 mb-2">Starter examples</h2><p class="small text-muted mb-0">Copy a paused automation to see how a keyword, public reply, and optional message template fit together.</p></div><a class="btn btn-sm btn-outline-primary align-self-start" href="{{ route('setup.index') }}">View setup guide</a></div><div class="row g-3 mt-1">@foreach($examples as $example)<div class="col-md-6"><div class="bg-white rounded-3 border p-3 h-100"><div class="fw-semibold">{{ $example['name'] }}</div><p class="small text-muted mt-2 mb-2">{{ $example['description'] }}</p><div class="small mb-1"><strong>Keyword:</strong> {{ $example['keyword'] }}</div><div class="small mb-3"><strong>Public reply:</strong> {{ $example['public_reply'] }}</div><form method="post" action="{{ route('rules.examples.duplicate', $example['key']) }}">@csrf<button class="btn btn-sm btn-outline-primary">Use this example</button></form></div></div>@endforeach</div></div></div>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Trigger</th><th>Source</th><th>Response</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
            @forelse($rules as $rule)
                <tr>
                    <td><div class="fw-semibold">{{ $rule->name }}</div><div class="small text-muted">{{ $rule->keyword ? 'Keyword: '.$rule->keyword : 'Matches every event' }}</div></td>
                    <td class="text-capitalize">{{ str_replace('_',' ',$rule->trigger_type) }}</td>
                    <td>{{ $rule->media?->name ?? 'Any Reel or post' }}<div class="small text-muted">{{ $rule->account?->username ? '@'.$rule->account->username : 'Any account' }}</div></td>
                    <td>
                        @if($rule->public_reply)<div class="small">Public reply</div>@endif
                        @if($rule->template)<div class="small">DM: {{ $rule->template->name }}</div>@endif
                        @if(!$rule->public_reply && !$rule->template)<span class="small text-muted">No response configured</span>@endif
                    </td>
                    <td><span class="badge-status {{ $rule->is_active ? 'status-ready' : 'status-neutral' }}">{{ $rule->is_active ? 'Active' : 'Paused' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('rules.edit',$rule) }}">Edit</a>
                        <form class="d-inline" method="post" action="{{ route('rules.destroy',$rule) }}" onsubmit="return confirm('Delete this automation rule? This cannot be undone.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><div class="icon-tile mx-auto mb-3">R</div><strong>No automation rules yet</strong><div class="mt-1 mb-2">A rule connects an incoming comment or message to a response. Start with a paused example above, then choose your account and media.</div><a class="btn btn-primary" href="{{ route('rules.create') }}">Create automation</a></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rules->links() }}</div>
@endsection
