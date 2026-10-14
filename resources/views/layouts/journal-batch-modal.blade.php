<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Ube Repository</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    @stack('styles')
</head>
<body class="jb-modal-document" data-journal-batch-embedded data-batch-title="{{ $modalTitle ?? ('Journal Batch — AY '.$batch->school_year) }}" @if(session('success')) data-batch-updated @if($closeOnSuccess ?? false) data-close-on-success @endif @endif>
    <main class="jb-modal-content">
        @if(session('success'))<p class="jb-success" role="status">{{ session('success') }}</p>@endif
        @yield('content')
    </main>
    @stack('scripts')
    <script src="{{ asset('js/journal-batch-embed.js') }}?v={{ filemtime(public_path('js/journal-batch-embed.js')) }}" defer></script>
</body>
</html>
