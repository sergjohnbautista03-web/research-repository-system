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

    @foreach($groups as $group)
        <section class="coord-card">
            <div class="coord-head">
                <div>
                    <h2>{{ $group['title'] }}</h2>
                    <p>{{ number_format($group['items']->count()) }} notification{{ $group['items']->count() === 1 ? '' : 's' }}</p>
                </div>
            </div>
            <div class="coord-list">
                @forelse($group['items'] as $item)
                    <article class="coord-item">
                        <div class="coord-item-main">
                            <h3>{{ $group['label']($item) }}</h3>
                            <p>{{ $group['meta']($item) }}</p>
                        </div>
                        <a href="{{ $group['route']($item) }}" class="coord-btn coord-btn-primary">{{ $group['action'] }}</a>
                    </article>
                @empty
                    <div class="coord-empty"><strong>{{ $group['empty'] }}</strong></div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
@endsection
