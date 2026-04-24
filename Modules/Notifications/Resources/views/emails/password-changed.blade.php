<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Security Alert</title>
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; padding:40px 0;">
    <tr>
        <td align="center">

            <!-- Container -->
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                <!-- Header -->
                <tr>
                    <td style="padding:30px; text-align:center; background:#0a1628;">
                        <img src="{{ $message->embed(public_path('emails/nti-logo.png')) }}"
                             alt="NTI Logo"
                             style="height:50px;">
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:40px; color:#0a1628;">

                        <h1 style="margin:0 0 16px; font-size:24px;">
                            Security Alert
                        </h1>

                        <p style="margin:0 0 20px; font-size:15px; color:#64748b; line-height:1.6;">
                            Your NTI account password has been successfully updated.
                        </p>

                        <p style="margin:0 0 16px; font-size:14px; color:#64748b;">
                            Hello {{ $userEmail }},
                        </p>

                        <!-- Account box -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                            <tr>
                                <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:14px; border-radius:10px;">
                                    <div style="font-size:12px; color:#64748b;">Account</div>
                                    <div style="font-weight:600; color:#0a1628;">
                                        {{ $userEmail }}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 12px; font-size:14px; color:#0a1628;">
                            If you made this change, you can safely ignore this email.
                        </p>

                        <p style="margin:0; font-size:14px; color:#0a1628;">
                            If you did NOT change your password, please reset it immediately or contact support.
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px; text-align:center; font-size:12px; color:#94a3b8; background:#f1f5f9;">
                        NTI Security Team • Keep your account secure
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
