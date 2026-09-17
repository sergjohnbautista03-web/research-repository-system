@extends('layouts.app')

@section('title', 'Ube Repository - Online Research Portal')

@section('content')
@php
    $departmentOptions = [
        'College of Accountancy and Business Education',
        'College of Computer Studies',
        'College of Criminal Justice Education',
        'College of Education',
        'College of Engineering and Architecture',
        'College of Maritime Studies',
    ];
    $hasFilters = request()->anyFilled(['search','department','year_from','year_to']);
    $activeDepartment = request('department');
    $minResearchYear = 2022;
    $maxResearchYear = 2026;
@endphp

<div class="home-page">
    <section class="home-hero" aria-labelledby="home-title">
        <div class="home-hero-inner">
            <div class="home-hero-copy">
                <span class="home-kicker">PhilCST Research Portal</span>
                <h1 id="home-title">Ube Research Repository</h1>
                <p>Discover approved academic research, browse by college, and open protected repository records in one focused workspace.</p>
            </div>
        </div>
    </section>

    <main class="home-shell">
        <section class="home-browse" aria-labelledby="browse-title">
            <div class="home-section-head">
                <div>
                    <span class="home-kicker">Browse by College</span>
                    <h2 id="browse-title">Explore departments</h2>
                </div>
                <p>Jump directly into research grouped by PhilCST college and academic area.</p>
            </div>

            <div class="department-strip">
                @foreach($departmentOptions as $dept)
                    @php
                        $shortName = collect(explode(' ', str_replace('and', '', $dept)))
                            ->filter(fn ($word) => strlen($word) > 2)
                            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                            ->implode('');
                    @endphp
                    <a href="{{ route('research.department', rawurlencode($dept)) }}"
                       class="department-chip {{ request()->is('department/*') && urldecode(request()->segment(2)) == $dept ? 'active' : '' }}">
                        <span>{{ $shortName }}</span>
                        {{ $dept }}
                    </a>
                @endforeach
            </div>
        </section>

        <div class="home-layout">
            <aside class="home-sidebar">
                <form action="{{ route('home') }}" method="GET" class="home-filter-card">
                    <h3>Refine Results</h3>
                    <p>Use specific filters when you need a narrower set of papers.</p>

                    <div class="home-search-row">
                        <label for="side-search">Keyword</label>
                        <input id="side-search" type="text" name="search" value="{{ request('search') }}" placeholder="Title, author, keyword..." autocomplete="off">
                    </div>

                    <div class="home-search-row">
                        <label for="side-field">Search By</label>
                        <select id="side-field" name="field">
                            <option value="all" {{ request('field', 'all') == 'all' ? 'selected' : '' }}>All Fields</option>
                            <option value="title" {{ request('field') == 'title' ? 'selected' : '' }}>Title</option>
                            <option value="author" {{ request('field') == 'author' ? 'selected' : '' }}>Author</option>
                            <option value="abstract" {{ request('field') == 'abstract' ? 'selected' : '' }}>Abstract</option>
                        </select>
                    </div>

                    <div class="home-search-row">
                        <label for="side-department">Department</label>
                        <select id="side-department" name="department">
                            <option value="">All Departments</option>
                            @foreach($departmentOptions as $dept)
                                <option value="{{ $dept }}" {{ $activeDepartment == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="home-year-grid">
                        <div class="home-search-row">
                            <label for="side-year-from">From</label>
                            <input id="side-year-from" type="number" name="year_from" value="{{ request('year_from') }}" placeholder="{{ $minResearchYear }}" min="{{ $minResearchYear }}" max="{{ $maxResearchYear }}">
                        </div>
                        <div class="home-search-row">
                            <label for="side-year-to">To</label>
                            <input id="side-year-to" type="number" name="year_to" value="{{ request('year_to') }}" placeholder="{{ $maxResearchYear }}" min="{{ $minResearchYear }}" max="{{ $maxResearchYear }}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Apply Filters</button>
                    @if($hasFilters)
                        <a href="{{ route('home') }}" class="btn btn-ghost btn-full">Clear Filters</a>
                    @endif
                </form>
            </aside>

            <section class="home-results" aria-labelledby="results-title">
                @if($hasFilters)
                    <div class="home-section-head compact">
                        <div>
                            <span class="home-kicker">Filtered Collection</span>
                            <h2 id="results-title">Search Results</h2>
                        </div>
                        <p>{{ number_format($researches->total()) }} {{ $researches->total() === 1 ? 'paper' : 'papers' }} found</p>
                    </div>

                    @if($researches->isEmpty())
                        <div class="empty-state home-empty">
                            <div class="empty-icon" aria-hidden="true">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </div>
                            <h3>No results found</h3>
                            <p>Try different keywords or remove some filters.</p>
                            <a href="{{ route('home') }}" class="btn btn-primary">Browse All Research</a>
                        </div>
                    @else
                        <div class="research-grid home-research-grid">
                            @foreach($researches as $item)
                                @include('components.research-card', ['research' => $item])
                            @endforeach
                        </div>
                        <div class="pagination-wrap">
                            {{ $researches->links() }}
                        </div>
                    @endif
                @else
                    <div class="home-section-head compact">
                        <div>
                            <span class="home-kicker">Featured Collection</span>
                            <h2 id="results-title">Featured Research</h2>
                        </div>
                        <p>Highly viewed papers from the approved PhilCST research collection.</p>
                    </div>

                    <div class="research-grid home-research-grid">
                        @forelse($featured as $item)
                            @include('components.research-card', ['research' => $item])
                        @empty
                            <div class="empty-state home-empty">
                                <p>No research available yet.</p>
                                @auth
                                    @if(auth()->user()->canSubmitResearch())
                                        <a href="{{ route('research.submit') }}" class="btn btn-primary">Be the first to submit</a>
                                    @endif
                                @endauth
                            </div>
                        @endforelse
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>

@endsection
