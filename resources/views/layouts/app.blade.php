<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <title>@yield('title', 'Instagram Automation')</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --app-ink:#111827; --app-muted:#667085; --app-border:#e4e7ec; --app-bg:#f6f7fb; --app-brand:#6d5dfc; --app-brand-dark:#5546e8; }
        body { background:var(--app-bg); color:var(--app-ink); min-height:100vh; }
        .app-navbar { background:#111827; box-shadow:0 1px 0 rgba(255,255,255,.08); }
        .brand-mark { width:34px; height:34px; border-radius:10px; display:grid; place-items:center; background:linear-gradient(135deg,#8b5cf6,#ec4899,#f97316); color:#fff; font-weight:800; }
        .navbar-brand { letter-spacing:-.02em; }
        .app-navbar .nav-link { border-radius:8px; padding:.55rem .75rem; color:#cbd5e1; }
        .app-navbar .nav-link:hover, .app-navbar .nav-link.active { color:#fff; background:rgba(255,255,255,.1); }
        .page-shell { padding-top:2rem; padding-bottom:3rem; }
        .eyebrow { color:var(--app-brand); font-size:.75rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .page-title { font-weight:750; letter-spacing:-.035em; }
        .page-subtitle { color:var(--app-muted); max-width:720px; }
        .card { border-color:var(--app-border); border-radius:16px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
        .metric-card { color:inherit; text-decoration:none; transition:transform .15s ease, box-shadow .15s ease; }
        .metric-card:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(16,24,40,.08); }
        .metric-value { font-size:2rem; line-height:1; font-weight:750; letter-spacing:-.04em; }
        .metric-label { color:var(--app-muted); font-size:.875rem; }
        .icon-tile { width:42px; height:42px; display:grid; place-items:center; border-radius:12px; background:#efedff; color:var(--app-brand); font-weight:800; }
        .status-dot { width:9px; height:9px; display:inline-block; border-radius:50%; margin-right:.4rem; }
        .status-ready { background:#dcfce7; color:#166534; }
        .status-working { background:#dbeafe; color:#1d4ed8; }
        .status-warning, .status-action { background:#fee2e2; color:#b42318; }
        .status-pending { background:#fef3c7; color:#92400e; }
        .status-neutral { background:#f2f4f7; color:#475467; }
        .badge-status { border-radius:999px; padding:.4rem .65rem; font-weight:650; font-size:.75rem; text-transform:capitalize; }
        .table > :not(caption) > * > * { padding:.85rem 1rem; border-color:#edf0f4; }
        .table thead th { color:#667085; font-size:.75rem; letter-spacing:.04em; text-transform:uppercase; font-weight:700; }
        .list-card { transition:border-color .15s ease, box-shadow .15s ease; }
        .list-card:hover { border-color:#c7d2fe; box-shadow:0 8px 18px rgba(16,24,40,.06); }
        .form-control, .form-select { min-height:44px; border-color:#d0d5dd; }
        .form-control:focus, .form-select:focus { border-color:#8b7ffc; box-shadow:0 0 0 .2rem rgba(109,93,252,.14); }
        .btn-primary { background:var(--app-brand); border-color:var(--app-brand); }
        .btn-primary:hover { background:var(--app-brand-dark); border-color:var(--app-brand-dark); }
        .empty-state { text-align:center; padding:3rem 1.5rem; color:var(--app-muted); }
        .activity-detail { background:#f8fafc; border:1px solid var(--app-border); border-radius:10px; padding:.75rem; }
        .code-break { white-space:pre-wrap; overflow-wrap:anywhere; }
        .setup-item { display:flex; gap:.85rem; align-items:flex-start; padding:.9rem 0; border-bottom:1px solid #edf0f4; }
        .setup-item:last-child { border-bottom:0; }
        .setup-icon { width:30px; height:30px; flex:0 0 30px; border-radius:50%; display:grid; place-items:center; font-weight:800; }
        .setup-number { width:36px; height:36px; display:grid; place-items:center; border-radius:50%; font-weight:800; }
        .setup-progress-wrap { width:min(100%, 360px); align-self:flex-end; }
        .setup-next { background:#f8f7ff; border:1px solid #ddd9ff; border-radius:12px; }
        .setup-step-card { border-top:3px solid #e4e7ec; }
        .setup-step-complete { border-top-color:#22c55e; }
        .media-import-preview { width:100%; aspect-ratio:1.25; object-fit:cover; border-radius:10px; background:#f2f4f7; display:block; }
        .automation-flow { background:linear-gradient(135deg,#f8f7ff,#f8fafc); border:1px solid #ddd9ff; border-radius:14px; padding:1rem; }
        .automation-flow-track { display:flex; align-items:stretch; gap:.65rem; overflow-x:auto; padding:.25rem .1rem .5rem; }
        .automation-flow-node { min-width:150px; flex:1; display:flex; flex-direction:column; gap:.35rem; padding:.85rem; background:#fff; border:1px solid var(--app-border); border-radius:11px; }
        .automation-flow-icon { width:26px; height:26px; display:grid; place-items:center; border-radius:50%; background:#efedff; color:var(--app-brand); font-size:.8rem; font-weight:800; }
        .automation-flow-arrow { align-self:center; color:var(--app-brand); font-size:1.35rem; font-weight:700; }
        .tour-backdrop { position:fixed; inset:0; z-index:1040; background:rgba(15,23,42,.62); }
        .tour-popover { position:absolute; z-index:1050; width:min(320px, calc(100vw - 32px)); background:#fff; border:1px solid var(--app-border); border-radius:14px; box-shadow:0 20px 50px rgba(15,23,42,.25); padding:1rem; }
        [data-tour-highlight] { position:relative; z-index:1045 !important; box-shadow:0 0 0 4px rgba(255,255,255,.92), 0 0 0 7px var(--app-brand) !important; border-radius:10px; }
        .nav-tabs .nav-link { color:#667085; font-weight:650; }
        .nav-tabs .nav-link.active { color:var(--app-brand); }
        @media (max-width: 991.98px) {
            .app-navbar .navbar-collapse { padding:1rem 0 .25rem; }
            .app-navbar .navbar-nav { gap:.25rem; }
            .page-shell { padding-top:1.25rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top" aria-label="Primary navigation">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">IA</span>
            <span>Instagram Automation</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNavigation" aria-controls="appNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="appNavigation">
            <div class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Overview</a>
                <a class="nav-link {{ request()->routeIs('setup.*') ? 'active' : '' }}" href="{{ route('setup.index') }}">Setup guide</a>
                <a class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}" data-tour-target="accounts">Accounts</a>
                <a class="nav-link {{ request()->routeIs('resources.*') ? 'active' : '' }}" href="{{ route('resources.index') }}" data-tour-target="media">Media</a>
                <a class="nav-link {{ request()->routeIs('templates.*') ? 'active' : '' }}" href="{{ route('templates.index') }}" data-tour-target="templates">Templates</a>
                <a class="nav-link {{ request()->routeIs('rules.*') ? 'active' : '' }}" href="{{ route('rules.index') }}" data-tour-target="rules">Rules</a>
                <a class="nav-link {{ request()->routeIs('activity.*') ? 'active' : '' }}" href="{{ route('activity.index') }}" data-tour-target="activity-nav">Activity</a>
                <form method="post" action="{{ route('logout') }}" class="ms-lg-2">
                    @csrf
                    <button class="btn btn-sm btn-outline-light w-100">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<main class="container page-shell">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm" role="alert">
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<div class="tour-backdrop d-none" data-tour-backdrop></div>
<div class="tour-popover d-none" data-tour-popover role="dialog" aria-live="polite" aria-label="Application tour">
    <div class="small text-uppercase fw-bold text-muted mb-2" data-tour-step-label></div>
    <h2 class="h5 mb-2" data-tour-title></h2>
    <p class="small text-muted mb-3" data-tour-description></p>
    <div class="d-flex justify-content-between align-items-center gap-2">
        <button class="btn btn-sm btn-link text-muted px-0" type="button" data-tour-skip>Skip tour</button>
        <div class="d-flex gap-2"><button class="btn btn-sm btn-outline-secondary" type="button" data-tour-prev>Previous</button><button class="btn btn-sm btn-primary" type="button" data-tour-next>Next</button></div>
    </div>
</div>
@stack('scripts')
<script>
(() => {
    const steps = [
        { target: '[data-tour-target="overview"]', title: 'Overview', description: 'Start here to see your automation health, recent runs, and the next action.' },
        { target: '[data-tour-target="metrics"]', title: 'Your results', description: 'These numbers show active rules, contacts, executions, and delivered messages.' },
        { target: '[data-tour-target="health"]', title: 'Setup and health', description: 'Use these checks to see whether your account, webhook, queue, delivery, and DM capability are ready.' },
        { target: '[data-tour-target="accounts"]', title: 'Accounts', description: 'Connect or reconnect your Instagram Professional account here.' },
        { target: '[data-tour-target="media"]', title: 'Media', description: 'Register a Reel or post, or import recent media from Instagram.' },
        { target: '[data-tour-target="templates"]', title: 'Templates', description: 'Create reusable messages with first-name and resource-link variables.' },
        { target: '[data-tour-target="rules"]', title: 'Rules', description: 'Connect a keyword and media item to the public reply and optional private message.' },
        { target: '[data-tour-target="activity-nav"]', title: 'Activity', description: 'Inspect webhook deliveries, executions, outgoing actions, and Meta errors.' },
    ];
    const backdrop = document.querySelector('[data-tour-backdrop]');
    const popover = document.querySelector('[data-tour-popover]');
    const title = document.querySelector('[data-tour-title]');
    const description = document.querySelector('[data-tour-description]');
    const stepLabel = document.querySelector('[data-tour-step-label]');
    let current = 0;
    const clear = () => document.querySelectorAll('[data-tour-highlight]').forEach(el => el.removeAttribute('data-tour-highlight'));
    const close = () => { clear(); backdrop?.classList.add('d-none'); popover?.classList.add('d-none'); };
    const render = () => {
        const step = steps[current];
        const target = document.querySelector(step.target);
        if (!target) { current += 1; if (current < steps.length) return render(); return close(); }
        clear(); target.setAttribute('data-tour-highlight', 'true');
        title.textContent = step.title; description.textContent = step.description; stepLabel.textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
        document.querySelector('[data-tour-prev]').disabled = current === 0;
        document.querySelector('[data-tour-next]').textContent = current === steps.length - 1 ? 'Finish' : 'Next';
        const rect = target.getBoundingClientRect();
        popover.style.top = Math.min(window.scrollY + rect.bottom + 12, window.scrollY + window.innerHeight - 220) + 'px';
        popover.style.left = Math.min(Math.max(16, rect.left), window.innerWidth - 340) + 'px';
    };
    const start = () => { current = 0; backdrop?.classList.remove('d-none'); popover?.classList.remove('d-none'); render(); };
    document.querySelector('[data-tour-start]')?.addEventListener('click', start);
    document.querySelector('[data-tour-next]')?.addEventListener('click', () => { if (current === steps.length - 1) { localStorage.setItem('instagram-automation-tour-seen', '1'); close(); } else { current += 1; render(); } });
    document.querySelector('[data-tour-prev]')?.addEventListener('click', () => { if (current > 0) { current -= 1; render(); } });
    document.querySelector('[data-tour-skip]')?.addEventListener('click', () => { localStorage.setItem('instagram-automation-tour-seen', '1'); close(); });
    backdrop?.addEventListener('click', close);
    if (new URLSearchParams(window.location.search).get('tour') === '1') start();
})();
</script>
</body>
</html>
