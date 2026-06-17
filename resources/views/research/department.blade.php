@extends('layouts.app')
@section('title', $department . ' — Ube Repository')

@section('content')

@php
$allDepts = [
    'College of Accountancy and Business Education' => [
        'Accountancy',
        'Business Administration-Marketing Mngt.',
        'Hospitality Management',
        'Tourism Management',
    ],
    'College of Computer Studies' => [
        'Computer Science',
        'Information Technology',
    ],
    'College of Criminal Justice Education' => [
        'Criminology',
    ],
    'College of Education' => [
        'Elementary Education',
        'Secondary Education-General Science',
    ],
    'College of Engineering and Architecture' => [
        'Civil Engineering',
        'Computer Engineering',
        'Electrical Engineering',
        'Electronics Engineering',
        'Mechanical Engineering',
    ],
    'College of Maritime Studies' => [
        'Marine Engineering',
        'Transportation',
    ],
];
@endphp

<div class="page-container">
    <aside class="sidebar">
        <div class="box">
            <h3 class="box-title">Departments</h3>
            <ul class="browse-list">
                @foreach($allDepts as $dept => $courses)
                <li>
                    <a href="{{ route('research.department', rawurlencode($dept)) }}"
                       class="{{ $department == $dept ? 'active' : '' }}"
                       style="font-weight:600;">
                        {{ $dept }}
                    </a>
                   
                </li>
                @endforeach
            </ul>
        </div>
    </aside>

    <main class="content">
        <div class="breadcrumb-nav">
            <a href="{{ route('home') }}">Home</a>
            <span>›</span>
            <a href="{{ route('research.department', rawurlencode($department)) }}">{{ $department }}</a>
            @isset($course)
            <span>›</span>
            <span>{{ $course }}</span>
            @endisset
        </div>

        <h2 class="section-title">
            {{ isset($course) ? $course : $department }}
            <span class="result-count">{{ $researches->total() }} papers</span>
        </h2>

        {{-- Course pills --}}
        @if(isset($allDepts[$department]))
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
            <a href="{{ route('research.department', rawurlencode($department)) }}"
               class="btn btn-sm {{ !request()->route('course') ? 'btn-primary' : 'btn-ghost' }}">
                All Courses
            </a>
            @foreach($allDepts[$department] as $c)
                <a href="{{ route('research.course', ['department' => rawurlencode($department), 'course' => rawurlencode($c)]) }}"
                class="btn btn-sm {{ isset($course) && $course == $c ? 'btn-primary' : 'btn-ghost' }}">
                {{ $c }}
            </a>
            @endforeach
        </div>
        @endif

        @if($researches->isEmpty())
            <div class="empty-state">
                <h3>No research in this department yet</h3>
                <a href="{{ route('home') }}" class="btn btn-primary">Browse All</a>
            </div>
        @else
            <div class="research-grid">
                @foreach($researches as $item)
                    @include('components.research-card', ['research' => $item])
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $researches->links() }}</div>
        @endif
    </main>
</div>
@endsection
