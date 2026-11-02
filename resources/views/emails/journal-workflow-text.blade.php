UBE RESEARCH REPOSITORY
{{ $details['heading'] }}

Hello {{ $recipientName }},

{{ $details['message'] }}

Journal Task / Batch: {{ $details['title'] }}
Journal Category: {{ $details['category'] }}
Academic Year: {{ $details['school_year'] }}
Department: {{ $details['department'] }}
Program: {{ $details['program'] }}
Dean: {{ $details['dean'] }}
Research Coordinator: {{ $details['coordinator'] }}
Submission Status: {{ $details['status'] }}
Date Assigned: {{ $details['assigned_at'] }}
Deadline: {{ $details['deadline'] }}
@if($details['event'] !== 'assigned')
{{ $details['date_label'] }}: {{ $details['date'] }}
@endif
@if($details['journals'])

Completed journals ({{ count($details['journals']) }}):
@foreach($details['journals'] as $journal)
- {{ $journal }}
@endforeach
@endif
@if($details['notes'])

Task instructions:
{{ $details['notes'] }}
@endif

{{ $details['button'] }}: {{ $details['url'] }}

Sign in using your registered account to open this task or submission.
