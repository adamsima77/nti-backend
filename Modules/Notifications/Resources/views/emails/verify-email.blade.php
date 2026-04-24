<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Email</title>
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
                            Verify your email address
                        </h1>

                        <p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
                            Thanks for joining Nitriansky Technický Inkubátor. Please confirm your email to activate your account.
                        </p>

                        <!-- Button -->
                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
                                    <a href="{{ $url }}"
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
                                        Verify Email
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- Fallback link -->
                        <p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
                            If the button doesn’t work, copy and paste this link:<br>
                            <a href="{{ $url }}" style="color:#0d5fbf; word-break:break-all;">
                                {{ $url }}
                            </a>
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
