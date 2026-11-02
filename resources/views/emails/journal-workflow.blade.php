<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $details['heading'] }}</title></head>
<body style="margin:0;padding:24px 12px;background:#f4effa;font-family:Arial,sans-serif;color:#32144e;">
    <main style="max-width:600px;margin:auto;padding:28px;background:#fff;border:1px solid #e8dcf3;border-radius:16px;">
        <p style="font-size:12px;letter-spacing:1px;color:#70468d;">UBE RESEARCH REPOSITORY</p>
        <h1 style="font-size:24px;line-height:1.3;">{{ $details['heading'] }}</h1>
        <p>Hello {{ $recipientName }},</p>
        <p style="line-height:1.6;">{{ $details['message'] }}</p>
        <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.6;">
            @foreach([
                'Journal Task / Batch' => $details['title'],
                'Journal Category' => $details['category'],
                'Academic Year' => $details['school_year'],
                'Department' => $details['department'],
                'Program' => $details['program'],
                'Dean' => $details['dean'],
                'Research Coordinator' => $details['coordinator'],
                'Submission Status' => $details['status'],
                'Date Assigned' => $details['assigned_at'],
                'Deadline' => $details['deadline'],
            ] as $label => $value)
                <tr><th scope="row" style="padding:9px 10px;border-bottom:1px solid #eee6f5;text-align:left;vertical-align:top;color:#70468d;width:38%;">{{ $label }}</th><td style="padding:9px 10px;border-bottom:1px solid #eee6f5;overflow-wrap:anywhere;">{{ $value }}</td></tr>
            @endforeach
            @if($details['event'] !== 'assigned')
                <tr><th scope="row" style="padding:9px 10px;border-bottom:1px solid #eee6f5;text-align:left;color:#70468d;">{{ $details['date_label'] }}</th><td style="padding:9px 10px;border-bottom:1px solid #eee6f5;">{{ $details['date'] }}</td></tr>
            @endif
        </table>
        @if($details['journals'])
            <h2 style="font-size:17px;margin-top:24px;">Completed journals ({{ count($details['journals']) }})</h2>
            <ul style="padding-left:22px;line-height:1.6;">@foreach($details['journals'] as $journal)<li>{{ $journal }}</li>@endforeach</ul>
        @endif
        @if($details['notes'])
            <h2 style="font-size:17px;margin-top:24px;">Task instructions</h2>
            <p style="white-space:pre-line;line-height:1.6;">{{ $details['notes'] }}</p>
        @endif
        <p style="margin:28px 0;"><a href="{{ $details['url'] }}" style="display:inline-block;padding:13px 20px;background:#6b2fa0;border-radius:8px;color:#fff;text-decoration:none;font-weight:bold;">{{ $details['button'] }}</a></p>
        <p style="font-size:12px;color:#806b91;line-height:1.6;">Sign in using your registered account to open this task or submission.<br>If the button does not work, open: <a href="{{ $details['url'] }}">{{ $details['url'] }}</a></p>
    </main>
</body>
</html>
