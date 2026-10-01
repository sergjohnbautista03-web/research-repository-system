@extends('layouts.admin')
@section('title', 'Activate Existing Users')
@section('page-title', 'Activate Existing Users')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/dean-semester-users.css') }}?v={{ filemtime(public_path('css/dean-semester-users.css')) }}">
@endpush
@section('content')
@include('admin.partials.activate-existing-users')
@endsection
@push('scripts')
<script src="{{ asset('js/dean-semester-users.js') }}?v={{ filemtime(public_path('js/dean-semester-users.js')) }}" defer></script>
@endpush
