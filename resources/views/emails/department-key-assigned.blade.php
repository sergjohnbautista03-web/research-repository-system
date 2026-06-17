<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Access Key</title>
</head>
<body style="margin:0; padding:0; background:#f2eef9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f2eef9; padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">
                <tr>
                    <td style="background:#1c044a; border-radius:16px 16px 0 0; padding:32px 40px; text-align:center;">
                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:rgba(190,150,255,.7); margin-bottom:6px;">Ube Research Repository</div>
                        <div style="font-size:22px; font-weight:800; color:#fff;">Department Key Sent</div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#fff; padding:36px 40px;">
                        <p style="font-size:16px; color:#1a0638; margin:0 0 16px; font-weight:600;">
                            Hi {{ $dean->name }},
                        </p>

                        <p style="font-size:14px; color:#5b3d8a; line-height:1.75; margin:0 0 20px;">
                            A new department access key has been created for your department. Please keep this information secure and share it only through your approved process.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eaf9; border:1.5px solid #c4a8e8; border-radius:12px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#6b2fa0; margin-bottom:12px;">Department Key Details</div>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Department:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $department }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Semester:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $semester }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>School Year:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $schoolYear }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Access Key:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0; font-weight:800;">{{ $accessKey }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#5b3d8a; padding:4px 0;"><strong>Expires At:</strong></td>
                                            <td style="font-size:13px; color:#1a0638; padding:4px 0;">{{ $expiresAt }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:13px; color:#a090bc; line-height:1.6; margin:0;">
                            This is an automated message from the Ube Research Repository.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f0eaf9; border-radius:0 0 16px 16px; padding:20px 40px; text-align:center;">
                        <p style="font-size:12px; color:#a090bc; margin:0;">
                            &copy; {{ date('Y') }} Ube Research Repository - PHILCST
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
