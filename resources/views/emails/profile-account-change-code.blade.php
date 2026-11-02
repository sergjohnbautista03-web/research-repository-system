<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Change Verification Code</title>
</head>
<body style="margin:0; padding:32px 16px; background:#f4f0fa; font-family:Arial,Helvetica,sans-serif; color:#32165b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto; background:#fff; border:1px solid #e8dff5; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="padding:28px 32px; background:#4b168c; color:#fff;">
                <div style="font-size:12px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; opacity:.8;">Ube Research Repository</div>
                <h1 style="margin:10px 0 0; font-size:23px;">Verify profile changes</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:30px 32px;">
                <p style="margin:0 0 18px; line-height:1.6;">Hello {{ $user->name }}, use this code to confirm your username or email address change. The code was sent to your currently registered email and expires in <strong>5 minutes</strong>.</p>
                <div style="padding:20px; border:1px dashed #b993e9; border-radius:12px; background:#f7f1ff; text-align:center; font-family:Consolas,'Courier New',monospace; font-size:34px; font-weight:700; letter-spacing:8px; color:#3b0f7a;">{{ $code }}</div>
                <p style="margin:18px 0 0; color:#6f5d83; font-size:13px; line-height:1.6;">This code can only be used once. Your profile changes will not be saved unless the code is verified. If you did not request this change, you can ignore this message.</p>
            </td>
        </tr>
    </table>
</body>
</html>
