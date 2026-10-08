@extends('layouts.app')
@section('title', 'Templates · Instagram Automation')
@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4"><div><div class="eyebrow mb-2">Reusable content</div><h1 class="page-title h2 mb-2">Message templates</h1><p class="page-subtitle mb-0">Prepare consistent private messages and preview variables before using them in rules.</p></div><a class="btn btn-primary" href="{{ route('templates.create') }}">Create template</a></div>
<div class="row g-3">
@forelse($items as $item)
    <div class="col-md-6"><a class="card list-card h-100 text-decoration-none text-reset" href="{{ route('templates.edit',$item) }}"><div class="card-body"><div class="d-flex align-items-start justify-content-between gap-2 mb-3"><div class="icon-tile">T</div><span class="badge-status {{ $item->is_active ? 'status-ready' : 'status-neutral' }}">{{ $item->is_active ? 'Active' : 'Paused' }}</span></div><h2 class="h5 mb-2">{{ $item->name }}</h2><p class="text-muted mb-0 code-break">{{ \Illuminate\Support\Str::limit($item->body, 180) }}</p></div></a></div>
@empty
    <div class="col-12"><div class="card"><div class="empty-state"><div class="icon-tile mx-auto mb-3">T</div><strong>No message templates</strong><div class="mt-1 mb-3">Create a reusable message with optional contact and resource variables.</div><a class="btn btn-primary" href="{{ route('templates.create') }}">Create template</a></div></div></div>
@endforelse
</div><div class="mt-4">{{ $items->links() }}</div>
@endsection
