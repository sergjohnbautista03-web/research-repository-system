@extends('layouts.app')
@section('title', $batch->title . ' — Ube Repository')

@section('content')
<div class="page-container">
    <main class="content">
        <div class="breadcrumb-nav">
            <a href="{{ route('home') }}">Home</a><span>&rsaquo;</span>
            <a href="{{ route('research.department', $batch->department) }}">{{ $batch->department }}</a>
        </div>
        <h2 class="section-title">{{ $batch->title }} <span class="result-count">{{ $researches->total() }} journals</span></h2>
        <p>{{ $batch->department }} &bull; Published on {{ $batch->batch_published_at->timezone('Asia/Manila')->format('F j, Y') }}</p>
        <div class="research-grid">
            @forelse($researches as $research)
                @include('components.research-card', ['research' => $research])
            @empty
                <p>No published journals are currently available in this batch.</p>
            @endforelse
        </div>
        {{ $researches->links('vendor.pagination.custom') }}
    </main>
</div>
@endsection
