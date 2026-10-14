@extends('layouts.admin')
@section('title', 'Journal Preparation Task')
@section('page-title', 'Journal Preparation Task')
@section('body-class', 'dean-handoffs-page')

@section('content')
@php
    $submissionCategories = \App\Models\Research::adminSubmissionCategories();
@endphp
@include('admin.partials.dean-handoffs')
@endsection
