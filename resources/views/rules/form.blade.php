@extends('layouts.app')
@section('title', ($rule->exists ? 'Edit' : 'New').' automation · Instagram Automation')
@section('content')
<div class="mb-4"><a class="small text-decoration-none" href="{{ route('rules.index') }}">← Back to rules</a><div class="eyebrow mt-3 mb-2">Automation builder</div><h1 class="page-title h2 mb-2">{{ $rule->exists ? 'Edit automation' : 'Create automation' }}</h1><p class="page-subtitle mb-0">Define the incoming event, audience and response. You can pause the rule at any time.</p></div>

<div class="alert alert-warning border-0 d-flex gap-3" role="status">
    <span class="fw-bold" aria-hidden="true">!</span>
    <div><strong>Private messages require Meta messaging capability.</strong><div class="small mt-1">You can configure and preview a DM here, but live delivery remains pending until Meta grants the app permission. Public comment replies can operate independently.</div></div>
</div>
@if($rule->exists && !$rule->is_active && !$rule->instagram_account_id && !$rule->media_resource_id)
<div class="alert alert-info border-0 d-flex gap-3" role="status">
    <span class="fw-bold" aria-hidden="true">i</span>
    <div><strong>This is a paused starter draft.</strong><div class="small mt-1">Choose the Instagram account and Reel/post that should trigger it, review the responses, then activate it when ready.</div></div>
</div>
@endif

<form method="post" action="{{ $rule->exists ? route('rules.update',$rule) : route('rules.store') }}" class="card">
    @csrf @if($rule->exists)@method('PUT')@endif
    <div class="card-body p-4">
        <div class="automation-flow mb-4" aria-live="polite">
            <div class="small text-uppercase fw-bold text-muted mb-3">How this automation works</div>
            <div class="automation-flow-track">
                <div class="automation-flow-node"><span class="automation-flow-icon">1</span><strong>Instagram comment</strong><span class="small text-muted" data-flow-media>Choose a Reel or post</span></div>
                <span class="automation-flow-arrow" aria-hidden="true">→</span>
                <div class="automation-flow-node"><span class="automation-flow-icon">2</span><strong>Keyword match</strong><span class="small text-muted" data-flow-keyword>Any comment</span></div>
                <span class="automation-flow-arrow" aria-hidden="true">→</span>
                <div class="automation-flow-node"><span class="automation-flow-icon">3</span><strong>Response</strong><span class="small text-muted" data-flow-response>Choose a response below</span></div>
                <span class="automation-flow-arrow" aria-hidden="true">→</span>
                <div class="automation-flow-node"><span class="automation-flow-icon">4</span><strong>Link sent</strong><span class="small text-muted" data-flow-link>No link selected</span></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 mb-4"><div class="icon-tile">1</div><div><h2 class="h5 mb-1">Trigger</h2><div class="small text-muted">Choose when this automation should run. The account is where it listens, the media limits it to one Reel or post, and the keyword is the text it looks for.</div></div></div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold" for="rule-name">Rule name</label><input id="rule-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name',$rule->name) }}" placeholder="Example: Send location link" required><div class="form-text">Visible only to your team.</div>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="trigger-type">Trigger</label><select id="trigger-type" name="trigger_type" class="form-select">@foreach(['comment_keyword'=>'Comment keyword','inbound_message'=>'Inbound DM'] as $value=>$label)<option value="{{ $value }}" @selected(old('trigger_type',$rule->trigger_type ?: 'comment_keyword')===$value)>{{ $label }}</option>@endforeach</select><div class="form-text" id="trigger-help">Run when a comment contains the keyword.</div></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="account-id">Instagram account</label><select id="account-id" name="instagram_account_id" class="form-select"><option value="">Any connected account</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected(old('instagram_account_id',$rule->instagram_account_id)===$a->id)>{{ $a->name }}{{ $a->username ? ' (@'.$a->username.')' : '' }}</option>@endforeach</select><div class="form-text">The connected account that should listen for this event.</div></div>
            <div class="col-md-6" id="media-field"><label class="form-label fw-semibold" for="media-id">Reel or post</label><select id="media-id" name="media_resource_id" class="form-select"><option value="">Any Reel or post</option>@foreach($media->where('type','media') as $m)<option value="{{ $m->id }}" @selected(old('media_resource_id',$rule->media_resource_id)===$m->id)>{{ $m->name }}</option>@endforeach</select><div class="form-text">The media resource that should trigger this rule. Add or import one from Media first.</div></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="keyword">Keyword</label><input id="keyword" name="keyword" class="form-control @error('keyword') is-invalid @enderror" value="{{ old('keyword',$rule->keyword) }}" placeholder="INFO"><div class="form-text">Leave empty to match every applicable comment or message.</div>@error('keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>

        <hr class="my-4">
        <div class="d-flex align-items-center gap-3 mb-4"><div class="icon-tile">2</div><div><h2 class="h5 mb-1">Response</h2><div class="small text-muted">A public reply appears below the comment. A template is the optional private message. A link resource fills <code>{resource_url}</code>.</div></div></div>
        <div class="row g-3">
            <div class="col-12" id="public-reply-field"><label class="form-label fw-semibold" for="public-reply">Public comment reply</label><textarea id="public-reply" name="public_reply" rows="3" class="form-control @error('public_reply') is-invalid @enderror" placeholder="Thanks! Check your messages for the details.">{{ old('public_reply',$rule->public_reply) }}</textarea><div class="form-text">Posted below the incoming Instagram comment.</div>@error('public_reply')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="template-id">Private message template</label><select id="template-id" name="message_template_id" class="form-select"><option value="" data-body="">No private message</option>@foreach($templates as $t)<option value="{{ $t->id }}" data-body="{{ $t->body }}" @selected(old('message_template_id',$rule->message_template_id)===$t->id)>{{ $t->name }}</option>@endforeach</select><div class="form-text">The selected template will be queued when Meta permits delivery.</div></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="resource-id">Link resource</label><select id="resource-id" name="resource_id" class="form-select"><option value="">No link resource</option>@foreach($media->where('type','link') as $m)<option value="{{ $m->id }}" @selected(old('resource_id',$rule->resource_id)===$m->id)>{{ $m->name }}</option>@endforeach</select><div class="form-text">Used to replace the <code>{resource_url}</code> variable.</div></div>
            <div class="col-12"><div class="activity-detail"><div class="small fw-semibold mb-2">Message preview</div><div id="template-preview" class="code-break text-muted">Select a template to preview the private message.</div></div></div>
        </div>

        <hr class="my-4">
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="is-active" name="is_active" value="1" @checked(old('is_active',$rule->is_active ?? true))><label class="form-check-label fw-semibold" for="is-active">Activate this automation immediately</label><div class="form-text">Turn this off to save the rule as a paused draft.</div></div>
    </div>
    <div class="card-footer bg-white d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 p-3">
        <a class="btn btn-outline-secondary" href="{{ route('rules.index') }}">Cancel</a>
        <button class="btn btn-primary">{{ $rule->exists ? 'Save changes' : 'Create automation' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const trigger = document.getElementById('trigger-type');
    const media = document.getElementById('media-field');
    const publicReply = document.getElementById('public-reply-field');
    const triggerHelp = document.getElementById('trigger-help');
    const template = document.getElementById('template-id');
    const preview = document.getElementById('template-preview');
    const mediaSelect = document.getElementById('media-id');
    const keyword = document.getElementById('keyword');
    const publicReplyInput = document.getElementById('public-reply');
    const resource = document.getElementById('resource-id');
    const flowMedia = document.querySelector('[data-flow-media]');
    const flowKeyword = document.querySelector('[data-flow-keyword]');
    const flowResponse = document.querySelector('[data-flow-response]');
    const flowLink = document.querySelector('[data-flow-link]');

    function updateTrigger() {
        const isDm = trigger.value === 'inbound_message';
        media.classList.toggle('d-none', isDm);
        publicReply.classList.toggle('d-none', isDm);
        triggerHelp.textContent = isDm ? 'Run when an incoming DM contains the keyword.' : 'Run when a comment contains the keyword.';
    }
    function updatePreview() {
        const body = template.options[template.selectedIndex]?.dataset.body || '';
        preview.textContent = body ? body.replaceAll('{first_name}', 'Alex').replaceAll('{resource_url}', 'https://example.com/resource') : 'Select a template to preview the private message.';
        preview.classList.toggle('text-muted', !body);
    }
    function updateFlow() {
        const mediaLabel = mediaSelect?.options[mediaSelect.selectedIndex]?.text || 'Choose a Reel or post';
        const resourceLabel = resource?.options[resource.selectedIndex]?.text || 'No link selected';
        const responseParts = [];
        if (publicReplyInput?.value.trim()) responseParts.push('Public reply');
        if (template?.value) responseParts.push('Private message');
        flowMedia.textContent = mediaSelect?.value ? mediaLabel : 'Choose a Reel or post';
        flowKeyword.textContent = keyword?.value.trim() ? 'Contains “' + keyword.value.trim() + '”' : 'Any comment';
        flowResponse.textContent = responseParts.length ? responseParts.join(' + ') : 'Choose a response below';
        flowLink.textContent = resource?.value ? resourceLabel : 'No link selected';
    }
    trigger.addEventListener('change', updateTrigger);
    template.addEventListener('change', updatePreview);
    [mediaSelect, keyword, publicReplyInput, resource].forEach(input => input?.addEventListener('input', updateFlow));
    [mediaSelect, resource].forEach(input => input?.addEventListener('change', updateFlow));
    updateTrigger(); updatePreview(); updateFlow();
})();
</script>
@endpush
