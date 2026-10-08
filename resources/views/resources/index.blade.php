@extends('layouts.app')
@section('title', 'Media · Instagram Automation')
@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4"><div><div class="eyebrow mb-2">Content library</div><h1 class="page-title h2 mb-2">Media and links</h1><p class="page-subtitle mb-0">Register Instagram posts for targeted rules and reusable destination links for messages.</p></div><a class="btn btn-primary" href="{{ route('resources.create') }}">Add resource</a></div>
<div class="row g-3">
@forelse($items as $item)
    <div class="col-md-6 col-xl-4"><a class="card list-card h-100 text-decoration-none text-reset" href="{{ route('resources.edit',$item) }}"><div class="card-body"><div class="d-flex align-items-start justify-content-between gap-2 mb-3"><div class="icon-tile">{{ $item->type === 'media' ? 'M' : '↗' }}</div><span class="badge-status {{ $item->is_active ? 'status-ready' : 'status-neutral' }}">{{ $item->is_active ? 'Active' : 'Paused' }}</span></div><h2 class="h5 mb-1">{{ $item->name }}</h2><div class="small text-muted text-capitalize mb-3">{{ $item->type === 'media' ? 'Instagram Reel or post' : 'Link resource' }}</div><div class="small code-break">{{ $item->instagram_media_id ?? $item->destination_url ?? 'Not configured' }}</div></div></a></div>
@empty
    <div class="col-12"><div class="card"><div class="empty-state"><div class="icon-tile mx-auto mb-3">M</div><strong>No media or links</strong><div class="mt-1 mb-3">Add a Reel, post or destination link before targeting it in a rule.</div><a class="btn btn-primary" href="{{ route('resources.create') }}">Add resource</a></div></div></div>
@endforelse
</div><div class="mt-4">{{ $items->links() }}</div>
@endsection
