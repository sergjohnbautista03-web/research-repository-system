<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Change Verification Code</title>
</head>
<body style="margin:0; padding:0; background:#f2eef9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f2eef9; padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; border-radius:16px; overflow:hidden; box-shadow:0 12px 36px rgba(43,13,78,0.12);">

                {{-- Header --}}
                <tr>
                    <td style="background:#1c044a; padding:36px 40px 28px; text-align:center;">
                        <div style="font-size:32px; line-height:1; margin-bottom:10px;">🔒</div>
                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:rgba(190,150,255,.75); margin-bottom:6px;">Ube Research Repository</div>
                        <div style="font-size:22px; font-weight:800; color:#fff; letter-spacing:-0.5px;">Password Change Request</div>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background:#fff; padding:36px 40px;">
                        <p style="font-size:16px; color:#1a0638; margin:0 0 16px; font-weight:600;">
                            Hello {{ $user->name }},
                        </p>

                        <p style="font-size:14px; color:#5b3d8a; line-height:1.75; margin:0 0 24px;">
                            We received a request to change the password for your account on the <strong>Ube Research Repository</strong>. Use the verification code below to confirm this change:
                        </p>

                        {{-- Verification Code Box --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f1ff; border:2px dashed #b993e9; border-radius:14px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:24px 20px; text-align:center;">
                                    <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#6b2fa0; margin-bottom:10px;">Your Verification Code</div>
                                    <div style="font-family:'Courier New', Courier, monospace; font-size:36px; font-weight:800; color:#2a085c; letter-spacing:8px; padding:6px 0;">
                                        {{ $code }}
                                    </div>
                                    <div style="font-size:12.5px; color:#8563a8; margin-top:8px;">
                                        Expires in <strong>{{ $expiresInMinutes }} minutes</strong> • Single-use only
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Security Notice --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 18px;">
                                    <div style="font-size:13px; font-weight:700; color:#9a3412; margin-bottom:4px;">
                                        ⚠️ Security Notice
                                    </div>
                                    <p style="font-size:12.5px; color:#7c2d12; margin:0; line-height:1.6;">
                                        If you did not request a password change, please ignore this email. Your password will not change until this code is verified. Never share this code with anyone.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:13px; color:#85739d; line-height:1.6; margin:0;">
                            Philippine College of Science and Technology<br>
                            Online Research Portal
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f0eaf9; padding:20px 40px; text-align:center; border-top:1px solid #e3d7f4;">
                        <p style="font-size:12px; color:#8d79a8; margin:0; line-height:1.5;">
                            © {{ date('Y') }} Ube Research Repository — PHILCST<br>
                            This is an automated security email. Please do not reply directly.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
