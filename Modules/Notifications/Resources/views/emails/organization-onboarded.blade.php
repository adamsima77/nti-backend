<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome Partner</title>
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; padding:40px 0;">
    <tr>
        <td align="center">

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
                            Welcome aboard, {{ $organizationName }}! 🤝
                        </h1>

                        <p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
                            Your organization has been successfully registered on the NTI platform. You're now part of a growing community of innovators, students, and industry leaders.
                        </p>

                        <!-- What you can do -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
                            <tr>
                                <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
                                    <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Here's what you can do now:</p>
                                    <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                                        📢 Post projects and opportunities<br>
                                        🎓 Connect with talented students<br>
                                        👥 Build and manage your team<br>
                                        🌐 Grow your presence in the NTI ecosystem
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- Button -->
                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
                                    <a href="{{ config('app.frontend_url') }}/dashboard"
                                       target="_blank"
                                       style="
                                            display:inline-block;
                                            padding:14px 28px;
                                            font-size:15px;
                                            color:#ffffff;
                                            text-decoration:none;
                                            font-weight:600;
                                            border-radius:8px;
                                            background-color:#0d5fbf;
                                       ">
                                        Go to Dashboard
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
                            If you have any questions, feel free to contact our support team anytime.
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px; text-align:center; font-size:12px; color:#94a3b8; background:#f1f5f9;">
                        © {{ date('Y') }} Nitriansky Technický Inkubátor
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
