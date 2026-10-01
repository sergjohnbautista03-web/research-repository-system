@extends('layouts.admin')
@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
@include('admin.partials.coordinator-styles')



<div class="coord-shell">
    <section class="coord-grid coord-grid-4">
        <div class="coord-stat"><span>New Dean Submissions</span><strong>{{ number_format($newHandoffs->count()) }}</strong><small>Documents ready for receipt.</small></div>
        <div class="coord-stat"><span>Returned</span><strong>{{ number_format($returnedResearches->count()) }}</strong><small>Needs revision and resubmission.</small></div>
        <div class="coord-stat"><span>Approved</span><strong>{{ number_format($approvedResearches->count()) }}</strong><small>Accepted by Admin.</small></div>
        <div class="coord-stat"><span>Drafts</span><strong>{{ number_format($drafts->count()) }}</strong><small>Coordinator action required.</small></div>
    </section>

    <section class="coord-card" data-notification-view>
        <div class="coord-head"><h2>Notifications</h2></div>
        @include('admin.partials.notification-controls')
    </section>
    <section class="coord-card">
        <div class="coord-head"><h2>Research Requiring Coordinator Action</h2></div>
        <div class="coord-list">
            @forelse($drafts as $item)
                <article class="coord-item">
                    <div class="coord-item-main"><h3>{{ $item->title }}</h3><p>Draft summary not yet submitted</p></div>
                    <a href="{{ route('admin.coordinator.summaries.edit', $item) }}" class="coord-btn coord-btn-primary">Continue</a>
                </article>
            @empty
                <div class="coord-empty"><strong>No draft summaries waiting for action.</strong></div>
            @endforelse
        </div>
    </section>
</div>
@endsection
