<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Academic Period Activated</title></head>
<body style="margin:0;padding:24px;background:#f4effa;font-family:Arial,sans-serif;color:#32144e;">
    <main style="max-width:560px;margin:auto;padding:28px;background:#fff;border:1px solid #e8dcf3;border-radius:16px;">
        <p style="font-size:12px;letter-spacing:1px;color:#70468d;">UBE RESEARCH REPOSITORY</p>
        <h1 style="font-size:24px;">A new academic period is active</h1>
        <p>Hello {{ $deanName }},</p>
        <p>The administrator has activated <strong>{{ $period['label'] }}</strong>.</p>
        @if($period['start_date'] || $period['end_date'])
            <p>Period dates: {{ $period['start_date'] ?: 'Not specified' }} to {{ $period['end_date'] ?: 'Not specified' }}.</p>
        @endif
        <p>You can now create or import Student and Faculty accounts. New accounts will be assigned to this academic period automatically.</p>
        <p>For continuing members, open <strong>Activate Existing Users</strong> and select the accounts to activate for the current semester. Their existing accounts and previous records will be kept.</p>
        <p style="margin:24px 0;"><a href="{{ $period['url'] }}" style="display:inline-block;padding:12px 18px;background:#6b2fa0;border-radius:8px;color:#fff;text-decoration:none;">Manage Users</a></p>
        <p style="font-size:12px;color:#806b91;">Activated on {{ $period['activated_at_label'] }}.</p>
    </main>
</body>
</html>
