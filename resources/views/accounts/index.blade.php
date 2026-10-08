@extends('layouts.app')
@section('title', 'Accounts · Instagram Automation')
@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div><div class="eyebrow mb-2">Connections</div><h1 class="page-title h2 mb-2">Instagram accounts</h1><p class="page-subtitle mb-0">Connect professional accounts through OAuth and monitor which API flow each account uses.</p></div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('meta.oauth.start') }}">Connect with Meta</a>
        @if(config('meta.instagram_app_id') && config('meta.instagram_app_secret'))<a class="btn btn-outline-primary" href="{{ route('meta.instagram.oauth.start') }}">Connect with Instagram Login</a>@endif
    </div>
</div>

<div class="alert alert-info border-0 d-flex gap-3" role="status"><span class="fw-bold" aria-hidden="true">i</span><div><strong>Use OAuth for normal connections.</strong><div class="small mt-1">Manual account entry is kept under advanced options for debugging and does not obtain a working access token.</div></div></div>

<div class="row g-3">
@forelse($items as $item)
    <div class="col-lg-6">
        <div class="card list-card h-100">
            <div class="card-body d-flex gap-3">
                <div class="brand-mark flex-shrink-0" aria-hidden="true">{{ strtoupper(substr($item->name,0,1)) }}</div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div><h2 class="h5 mb-1">{{ $item->name }}</h2><div class="text-muted">{{ $item->username ? '@'.$item->username : $item->instagram_user_id }}</div></div>
                        <span class="badge-status {{ $item->is_active ? 'status-ready' : 'status-neutral' }}">{{ $item->is_active ? 'Connected' : 'Paused' }}</span>
                    </div>
                    <dl class="row small mt-3 mb-0">
                        <dt class="col-5 text-muted fw-normal">Login method</dt><dd class="col-7 text-capitalize mb-2">{{ str_replace('_',' ',$item->auth_mode) }}</dd>
                        <dt class="col-5 text-muted fw-normal">Instagram ID</dt><dd class="col-7 mb-2 code-break">{{ $item->instagram_user_id }}</dd>
                        <dt class="col-5 text-muted fw-normal">Token</dt><dd class="col-7 mb-0">{{ $item->access_token ? 'Stored securely' : 'Missing' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('accounts.edit',$item) }}">Manage</a><a class="btn btn-sm btn-outline-primary" href="{{ $item->auth_mode === 'instagram_login' ? route('meta.instagram.oauth.start') : route('meta.oauth.start') }}">Reconnect</a></div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="empty-state"><div class="brand-mark mx-auto mb-3">IA</div><strong>No Instagram account connected</strong><div class="mt-1 mb-3">Connect an Instagram professional account to receive webhooks and run automations.</div><a class="btn btn-primary" href="{{ route('meta.oauth.start') }}">Connect with Meta</a></div></div></div>
@endforelse
</div>
<div class="d-flex justify-content-between align-items-center mt-4"><details><summary class="small text-muted">Advanced options</summary><a class="btn btn-sm btn-outline-secondary mt-2" href="{{ route('accounts.create') }}">Add account manually</a></details><div>{{ $items->links() }}</div></div>
@endsection
