@extends('layouts.app')

@section('title', 'Setup guide · Instagram Automation')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <div class="eyebrow mb-2">First-run setup</div>
        <h1 class="page-title h2 mb-2">Build your first automation</h1>
        <p class="page-subtitle mb-0">Follow these five steps in order. Completed steps are detected automatically, so you can leave and return at any time.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Back to overview</a>
</div>

<div class="card setup-hero mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-4">
            <div>
                <div class="small text-uppercase fw-bold text-muted mb-2">Progress</div>
                <div class="display-6 fw-semibold">{{ $setup['complete'] }} of {{ $setup['total'] }} complete</div>
                <p class="text-muted mb-0 mt-2">Your next recommended action is shown below.</p>
            </div>
            <div class="setup-progress-wrap">
                <div class="setup-progress-label d-flex justify-content-between small text-muted mb-2"><span>Setup progress</span><span>{{ $setup['percent'] }}%</span></div>
                <div class="progress" role="progressbar" aria-label="Setup progress" aria-valuenow="{{ $setup['percent'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $setup['percent'] }}%"></div></div>
            </div>
        </div>
        @if($setup['next'])
            <div class="setup-next mt-4 p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div><div class="small text-uppercase fw-bold text-muted mb-1">Next step</div><strong>{{ $setup['next']['label'] }}</strong><div class="small text-muted mt-1">{{ $setup['next']['description'] }}</div></div>
                <a class="btn btn-primary text-nowrap" href="{{ $setup['next']['url'] }}">Continue setup</a>
            </div>
        @else
            <div class="alert alert-success border-0 mb-0 mt-4"><strong>Your setup is complete.</strong> Add a fresh matching comment to test a real automation.</div>
        @endif
    </div>
</div>

<div class="row g-3">
    @foreach($setup['steps'] as $index => $step)
        @php
            $stateLabel = match($step['state']) { 'complete' => 'Complete', 'in_progress' => 'In progress', default => 'Not started' };
            $stateClass = match($step['state']) { 'complete' => 'status-ready', 'in_progress' => 'status-working', default => 'status-neutral' };
            $stateIcon = match($step['state']) { 'complete' => '✓', 'in_progress' => '→', default => $index + 1 };
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 setup-step-card {{ $step['state'] === 'complete' ? 'setup-step-complete' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><span class="setup-number {{ $stateClass }}">{{ $stateIcon }}</span><span class="badge-status {{ $stateClass }}">{{ $stateLabel }}</span></div>
                    <h2 class="h5 mb-2">{{ $index + 1 }}. {{ $step['label'] }}</h2>
                    <p class="text-muted small mb-4">{{ $step['description'] }}</p>
                    <a class="btn btn-sm {{ $step['state'] === 'complete' ? 'btn-outline-secondary' : 'btn-primary' }}" href="{{ $step['url'] }}">{{ $step['state'] === 'complete' ? 'Review' : 'Open step' }}</a>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mt-4">
    <div class="card-body p-4">
        <h2 class="h5 mb-2">Need a tour?</h2>
        <p class="text-muted mb-3">The tour highlights the main areas of the application and can be replayed from the Overview page.</p>
        <a class="btn btn-outline-primary" href="{{ route('dashboard', ['tour' => '1']) }}">Take the application tour</a>
    </div>
</div>
@endsection
