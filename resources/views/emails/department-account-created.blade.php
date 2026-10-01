<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Your account is ready</title></head>
<body style="font-family:Arial,sans-serif;color:#241339;line-height:1.6">
    <h2>Welcome to UBE Repository</h2>
    <p>Hello {{ $account->name }},</p>
    <p>Your administrator has created your {{ $roleLabel }} account for {{ $account->department }}.</p>
    <p><strong>Login ID:</strong> {{ $account->student_id }}<br>
       <strong>Default password:</strong> {{ $initialPassword }}</p>
    <p><a href="{{ route('login') }}">Sign in to UBE Repository</a></p>
    <p>Please change your default password after signing in and keep your login details private.</p>
</body>
</html>
