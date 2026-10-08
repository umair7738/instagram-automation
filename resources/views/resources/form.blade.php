@extends('layouts.app')
@section('title', ($item->exists ? 'Edit' : 'New').' resource · Instagram Automation')
@section('content')
<div class="mb-4"><a class="small text-decoration-none" href="{{ route('resources.index') }}">← Back to media and links</a><div class="eyebrow mt-3 mb-2">Content library</div><h1 class="page-title h2 mb-2">{{ $item->exists ? 'Edit resource' : 'Add resource' }}</h1><p class="page-subtitle mb-0">Use a media item to target one Reel or post. Use a link resource inside message templates.</p></div>
<form class="card" method="post" action="{{ $item->exists?route('resources.update',$item):route('resources.store') }}">@csrf @if($item->exists)@method('PUT')@endif
    <div class="card-body p-4"><div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold" for="resource-name">Name</label><input id="resource-name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name',$item->name) }}" placeholder="Example: Manali Reel" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label fw-semibold" for="resource-type">Type</label><select id="resource-type" class="form-select" name="type"><option value="media" @selected(old('type',$item->type ?: 'media')==='media')>Instagram Reel or post</option><option value="link" @selected(old('type',$item->type)==='link')>Destination link</option></select></div>
        <div class="col-12 media-input">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div><label class="form-label fw-semibold mb-1" for="media-id">Instagram media ID</label><div class="form-text mt-0">This is the numeric ID Meta sends with a comment. Importing is recommended because usernames, shortcodes, and links are not media IDs.</div></div>
                @if($accounts->isNotEmpty())<button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#media-import-modal">Import from Instagram</button>@endif
            </div>
            <input id="media-id" class="form-control mt-2 @error('instagram_media_id') is-invalid @enderror" name="instagram_media_id" value="{{ old('instagram_media_id',$item->instagram_media_id) }}" placeholder="18019326794001472">@error('instagram_media_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 media-input"><label class="form-label fw-semibold" for="permalink">Instagram permalink</label><input id="permalink" type="url" class="form-control @error('permalink') is-invalid @enderror" name="permalink" value="{{ old('permalink',$item->permalink) }}" placeholder="https://www.instagram.com/reel/..."><div class="form-text">Used for reference in the dashboard.</div>@error('permalink')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12 link-input"><label class="form-label fw-semibold" for="destination-url">Destination URL</label><input id="destination-url" type="url" class="form-control @error('destination_url') is-invalid @enderror" name="destination_url" value="{{ old('destination_url',$item->destination_url) }}" placeholder="https://example.com/offer"><div class="form-text">This replaces <code>{resource_url}</code> in the selected template.</div>@error('destination_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" role="switch" id="resource-active" name="is_active" value="1" @checked(old('is_active',$item->is_active??true))><label class="form-check-label fw-semibold" for="resource-active">Available to automation rules</label></div></div>
    <div class="card-footer bg-white d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 p-3"><a class="btn btn-outline-secondary" href="{{ route('resources.index') }}">Cancel</a><button class="btn btn-primary">Save resource</button></div>
</form>
@if($accounts->isNotEmpty())
<div class="modal fade" id="media-import-modal" tabindex="-1" aria-labelledby="media-import-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><div><h2 class="modal-title h5 mb-1" id="media-import-title">Import recent Instagram media</h2><div class="small text-muted">Choose a Reel or post and we will fill in its numeric Graph ID.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label fw-semibold" for="import-account">Instagram account</label>
                <select class="form-select mb-3" id="import-account">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}{{ $account->username ? ' (@'.$account->username.')' : '' }}</option>@endforeach</select>
                <div class="alert alert-danger d-none" data-import-error></div>
                <div class="text-center text-muted py-4 d-none" data-import-loading>Loading recent media…</div>
                <div class="row g-3" data-import-results></div>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="button" data-import-load>Load recent media</button></div>
        </div>
    </div>
</div>
@endif
@endsection
@push('scripts')
<script>
(() => {
    const type = document.getElementById('resource-type');
    const render = () => {
        document.querySelectorAll('.media-input').forEach(el => el.classList.toggle('d-none', type.value !== 'media'));
        document.querySelectorAll('.link-input').forEach(el => el.classList.toggle('d-none', type.value !== 'link'));
    };
    type.addEventListener('change', render);
    render();

    const load = document.querySelector('[data-import-load]');
    const account = document.getElementById('import-account');
    const results = document.querySelector('[data-import-results]');
    const error = document.querySelector('[data-import-error]');
    const loading = document.querySelector('[data-import-loading]');
    if (!load) return;
    load.addEventListener('click', async () => {
        results.innerHTML = ''; error.classList.add('d-none'); loading.classList.remove('d-none'); load.disabled = true;
        try {
            const response = await fetch('{{ route('resources.instagram-media') }}?account_id=' + encodeURIComponent(account.value), { headers: { Accept: 'application/json' } });
            const body = await response.json();
            if (!response.ok) throw new Error(body.message || 'Instagram could not load media.');
            if (!body.data?.length) throw new Error('No recent media was returned for this account.');
            body.data.forEach(item => {
                const card = document.createElement('button');
                card.type = 'button'; card.className = 'col-md-6 text-start border-0 bg-transparent';
                const caption = (item.caption || 'No caption').slice(0, 100);
                const shell = document.createElement('span');
                shell.className = 'd-block border rounded-3 p-3 h-100';
                const previewUrl = item.thumbnail_url || item.media_url;
                if (previewUrl) {
                    const preview = document.createElement('img');
                    preview.className = 'media-import-preview mb-3';
                    preview.src = previewUrl;
                    preview.alt = caption === 'No caption' ? 'Instagram media preview' : caption;
                    preview.loading = 'lazy';
                    preview.referrerPolicy = 'no-referrer';
                    preview.addEventListener('error', () => preview.remove());
                    shell.appendChild(preview);
                }
                const typeLabel = document.createElement('strong');
                typeLabel.className = 'd-block';
                typeLabel.textContent = item.media_type || 'Instagram media';
                const captionLabel = document.createElement('span');
                captionLabel.className = 'small text-muted d-block mt-1';
                captionLabel.textContent = caption;
                const actionLabel = document.createElement('span');
                actionLabel.className = 'small text-primary d-block mt-2';
                actionLabel.textContent = 'Use this media';
                shell.append(typeLabel, captionLabel, actionLabel);
                card.appendChild(shell);
                card.addEventListener('click', () => {
                    document.getElementById('media-id').value = item.id || '';
                    document.getElementById('permalink').value = item.permalink || '';
                    const name = document.getElementById('resource-name');
                    if (!name.value) name.value = caption === 'No caption' ? 'Instagram media ' + item.id : caption;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('media-import-modal')).hide();
                });
                results.appendChild(card);
            });
        } catch (exception) { error.textContent = exception.message; error.classList.remove('d-none'); }
        finally { loading.classList.add('d-none'); load.disabled = false; }
    });
})();
</script>
@endpush
