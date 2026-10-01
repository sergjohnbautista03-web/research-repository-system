@extends('layouts.admin')
@section('title', 'Add User')
@section('page-title', 'Add User')
@section('content')
<section class="member-form-card">
    <h2>Add User</h2>
    <p>Create a student or faculty account for {{ auth()->user()->department }}.</p>
    @include('admin.partials.add-user-form')
</section>
<link rel="stylesheet" href="{{ asset('css/dean-add-user.css') }}?v={{ filemtime(public_path('css/dean-add-user.css')) }}">
<script src="{{ asset('js/dean-add-user.js') }}?v={{ filemtime(public_path('js/dean-add-user.js')) }}"></script>
@endsection
