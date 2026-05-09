<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Received</title>
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
                            Thank you, {{ $organizationName }}! 🎉
                        </h1>

                        <p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
                            Your organization has been successfully registered on the NTI platform.
                            Our team will review your application and get back to you shortly.
                        </p>

                        <!-- Status box -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
                            <tr>
                                <td style="background:#fef9c3; border-left:4px solid #eab308; border-radius:8px; padding:20px;">
                                    <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                                        ⏳ Account pending approval
                                    </p>
                                    <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                                        Your account is currently under review. You will not be able to log in until our team verifies your organization. This typically takes 1-2 business days.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- What happens next -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
                            <tr>
                                <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
                                    <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">What happens next:</p>
                                    <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                                        🔍 NTI team reviews your organization details<br>
                                        ✅ You receive an approval confirmation email<br>
                                        🚀 You get full access to the platform
                                    </p>
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
