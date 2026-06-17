<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account Approved</title>
</head>
<body style="margin:0; padding:0; background:#f2eef9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f2eef9; padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">
                <tr>
                    <td style="background:#1c044a; border-radius:16px 16px 0 0; padding:32px 40px; text-align:center;">
                        <div style="font-size:28px; margin-bottom:8px;">*</div>
                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:rgba(190,150,255,.7); margin-bottom:6px;">Ube Research Repository</div>
                        <div style="font-size:22px; font-weight:800; color:#fff; letter-spacing:-0.5px;">Student Account Approved</div>
                    </td>
                </tr>

                <tr>
                    <td style="background:#fff; padding:36px 40px;">
                        <p style="font-size:16px; color:#1a0638; margin:0 0 16px; font-weight:600;">
                            Hi {{ $user->name }},
                        </p>

                        <p style="font-size:14px; color:#5b3d8a; line-height:1.75; margin:0 0 20px;">
                            Your student account on the <strong>Ube Research Repository</strong> has been approved by an admin. Your official Student ID has now been assigned.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eaf9; border:1.5px solid #c4a8e8; border-radius:12px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#6b2fa0; margin-bottom:12px;">Your Account Details</div>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Name:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $user->name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Email:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $user->email }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Department:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $user->department ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Student ID:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0; font-weight:800;">{{ $user->student_id }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#faf8ff; border:1px solid #e8dff5; border-radius:10px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:14px 18px;">
                                    <p style="font-size:13px; color:#3b0f7a; margin:0 0 8px;">
                                        <strong>You can now log in using your email and password.</strong>
                                    </p>
                                    <p style="font-size:13px; color:#5b3d8a; margin:0; line-height:1.6;">
                                        Keep your assigned Student ID for future account verification and student-related transactions inside the system.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ url('/login') }}"
                                       style="display:inline-block; background:#3b0f7a; color:#fff; font-size:14px; font-weight:700; padding:13px 32px; border-radius:50px; text-decoration:none; box-shadow:0 4px 16px rgba(59,15,122,.3);">
                                        Login to Your Account ->
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:13px; color:#a090bc; line-height:1.6; margin:0;">
                            If you have any questions about your account, please contact the system administrator.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background:#f0eaf9; border-radius:0 0 16px 16px; padding:20px 40px; text-align:center;">
                        <p style="font-size:12px; color:#a090bc; margin:0;">
                            &copy; {{ date('Y') }} Ube Research Repository - PHILCST<br>
                            This is an automated message. Please do not reply directly.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
