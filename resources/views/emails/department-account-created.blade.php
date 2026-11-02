<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Your account is ready</title></head>
<body style="font-family:Arial,sans-serif;color:#241339;line-height:1.6">
    <h2>Welcome to UBE Repository</h2>
    <p>Hello {{ $account->name }},</p>
    <p>Your {{ $roleLabel }} account has been created for {{ $account->department }}.</p>
    <p><strong>Login ID:</strong> {{ $account->student_id }}<br>
       <strong>Temporary password:</strong> {{ $initialPassword }}</p>
    @if($account->is_active)
        <p><a href="{{ route('login') }}">Sign in to UBE Repository</a></p>
        <p>Please change your temporary password after signing in and keep your login details private.</p>
    @else
        <p>Your account is not active yet because there is no active semester. You can sign in after your Dean activates the account for a semester.</p>
        <p>Keep your temporary password private. Change it after your first sign-in.</p>
    @endif
</body>
</html>
