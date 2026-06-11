<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $link = config('app.frontend_url');

        $templates = [
            [
                'slug'       => 'bulk_call_2024_open',
                'subject'    => 'Ahoj {{ $userName }}, výzva 2024 je otvorená',
                'type'       => 'bulk',
                'is_active'  => true,
                'body_html'  => '<h2 style="margin:0 0 8px; font-size:22px; font-weight:600; color:#0a1628;">
    Hi, {{ $userName }}!
</h2>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    We have an important update for you regarding the NTI grant portal.
    Please read the following information carefully.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f0fdf4; border-left:4px solid #16a34a; border-radius:8px; padding:20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                🚀 Call for Applications 2024 is Open
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                Team registration has started. The application deadline is
                <strong style="color:#0a1628;">July 31, 2024</strong>.
                Projects can receive support up to €50,000.
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">
                What you need to apply:
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2.2;">
                👥 Team of 2 to 5 members<br>
                📄 Project proposal (max. 5 pages)<br>
                📊 Preliminary budget<br>
                🏫 At least one member with student status
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
           <a href="'.$link.'" target="_blank"
               style="display:inline-block; background:#0a1628; color:#ffffff; text-decoration:none;
                      font-size:15px; font-weight:600; padding:14px 32px; border-radius:8px;">
                Go to Portal →
            </a>
        </td>
    </tr>
</table>

<p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
    This email was sent to the address associated with your account.
    If you think you received it by mistake, please contact us at
    <a href="mailto:support@nti.sk" style="color:#64748b;">support@nti.sk</a>.
</p>'
            ],
            [
                'slug'       => 'mentorship_session_created',
                'subject'    => 'Ahoj {{ $fullName }}, nová mentorska konzultácia je naplánovaná',
                'type'       => 'transactional',
                'is_active'  => true,
                'body_html'  => '<h2 style="margin:0 0 8px; font-size:22px; font-weight:600; color:#0a1628;">
    Ahoj, {{ $fullName }}!
</h2>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Váš mentor <strong>{{ $mentorName }}</strong> naplánoval novú konzultáciu pre program <strong>{{ $callName }}</strong>. Pozri si detaily stretnutia nižšie a priprav sa na stretnutie.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f0fdf4; border-left:4px solid #16a34a; border-radius:8px; padding:20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                📅 {{ $title }}
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                <strong style="color:#0a1628;">Čas:</strong> {{ $formattedDate }}<br>
                <strong style="color:#0a1628;">Trvanie:</strong> {{ $duration }} minút<br>
                <strong style="color:#0a1628;">Forma:</strong> {{ $formaText }}<br>
                <strong style="color:#0a1628;">Odkaz / Miesto:</strong> {{ $meetingUrlText }}
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">
                {{ $agendaTitle }}
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6; white-space: pre-line;">
                {{ $agendaBody }}
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
           <a href="{{ $actionURL }}" target="_blank"
               style="display:inline-block; background:#0a1628; color:#ffffff; text-decoration:none;
                      font-size:15px; font-weight:600; padding:14px 32px; border-radius:8px;">
                Otvoriť konzultáciu →
            </a>
        </td>
    </tr>
</table>

<p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
    Tento email bol odoslaný na adresu prepojenú s tvojím účtom na NTI portáli.
    Ak si myslíš, že si správu dostal omylom, kontaktuj nás na
    <a href="mailto:support@nti.sk" style="color:#64748b;">support@nti.sk</a>.
</p>'
            ],
            [
                'slug'    => 'organization_onboarded',
                'subject' => 'Thank you, {{ $organizationName }}!',
                'body_html' => '

<h1 style="margin:0 0 16px; font-size:24px;">Thank you, {{ $organizationName }}! 🎉</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Your organization has been successfully registered on the NTI platform.
    Our team will review your application and get back to you shortly.
</p>

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
</p>',
            ],
            [
                'slug'    => 'student_onboarded',
                'subject' => 'You\'re in, {{ $userName }}!',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">You\'re in, {{ $userName }}! 🎓</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Your student profile has been successfully set up. You now have full access to the NTI platform and everything it has to offer.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Here\'s what you can do now:</p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                🚀 Browse and apply for projects<br>
                🤝 Connect with mentors and partners<br>
                📁 Showcase your portfolio<br>
                🏆 Join teams and competitions
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="'.$link.'/student" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Go to Dashboard</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If you have any questions, feel free to contact our support team anytime.
</p>',
            ],
            [
                'slug'    => 'user_account_banned',
                'subject' => 'Important update regarding your NTI account',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px; color:#1e293b;">Account Notice</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Hello {{ $userName }},<br><br>
    We are writing to inform you that your account on the NTI platform has been suspended following a comprehensive review by our administration team.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#fef2f2; border-left:4px solid #ef4444; border-radius:8px; padding:20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#991b1b;">
                🛑 Access Restricted
            </p>
            <p style="margin:0; font-size:14px; color:#7f1d1d; line-height:1.6;">
                Your credentials have been deactivated, and you can no longer authenticate, manage profile details, or interact within active workspaces. Any pending activities or submissions linked to this account have been placed on hold.
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Next steps & Appeal process:</p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                ⚖️ Review our Terms of Service and usage guidelines<br>
                ✉️ Prepare your account information if initiating an inquiry<br>
                📥 Reach out to support directly to request an administrative review
            </p>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If you believe this restriction was placed in error, or if you have any questions, please contact our support team immediately.
</p>',
            ],

            [
                'slug'    => 'welcome_email',
                'subject' => 'Welcome to NTI, {{ $userName }}!',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Welcome to NTI, {{ $userName }} 👋</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Your email has been verified successfully. You\'re almost there — just one more step before you can start exploring everything Nitriansky Technický Inkubátor has to offer.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Complete your setup in 2 simple steps:</p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                ✅ Step 1 — Verify your email<br>
                🔲 Step 2 — Complete your onboarding profile
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="'.$link.'/auth/onboarding" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Complete Onboarding</a>
        </td>
    </tr>
</table>',
            ],
            [
                'slug'    => 'verify_email',
                'subject' => 'Verify your email address',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Verify your email address</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Thanks for joining Nitriansky Technický Inkubátor. Please confirm your email to activate your account.
</p>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $verificationUrl }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Verify Email</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If the button doesn\'t work, copy and paste this link:<br>
    <a href="{{ $verificationUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $verificationUrl }}</a>
</p>',
            ],
            [
                'slug'    => 'reset_password',
                'subject' => 'Reset your password',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Reset your password</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Hello {{ $user->name }},<br><br>
    We received a request to reset your password. Click the button below to set a new one.
</p>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Reset Password</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If the button doesn\'t work, copy and paste this link:<br>
    <a href="{{ $url }}" style="color:#0d5fbf; word-break:break-all;">{{ $url }}</a>
</p>',
            ],
            [
                'slug'    => 'password_changed',
                'subject' => 'Security Alert — Password Changed',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Security Alert</h1>
<p style="margin:0 0 20px; font-size:15px; color:#64748b; line-height:1.6;">Your NTI account password has been successfully updated.</p>
<p style="margin:0 0 16px; font-size:14px; color:#64748b;">Hello {{ $userEmail }},</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:14px; border-radius:10px;">
            <div style="font-size:12px; color:#64748b;">Account</div>
            <div style="font-weight:600; color:#0a1628;">{{ $userEmail }}</div>
        </td>
    </tr>
</table>

<p style="margin:0 0 12px; font-size:14px; color:#0a1628;">If you made this change, you can safely ignore this email.</p>
<p style="margin:0; font-size:14px; color:#0a1628;">If you did NOT change your password, please reset it immediately or contact support.</p>',
            ],
            [
                'slug'    => 'milestone_status_changed',
                'subject' => 'Zmena stavu míľnika: {{ $milestoneName }}',
                'body_html' => '
<p style="margin:0 0 16px; font-size:15px; color:#0a1628; line-height:1.6;">Ahoj {{ $userName }},</p>
<p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
    Míľnik <strong>{{ $milestoneName }}</strong> prešiel z stavu <strong>{{ $oldStatus ?? \'-\' }}</strong> na <strong>{{ $newStatus ?? \'-\' }}</strong>.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:16px;">
            <p style="margin:0; font-size:14px; color:#64748b;">
                Deadline: <strong>{{ $deadline }}</strong><br>
                Akciu vykonal: <strong>{{ $actorName }}</strong>
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="'.$link.'/student" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Zobraziť projekt</a>
        </td>
    </tr>
</table>',
            ],
            [
                'slug' => 'contact_message_received',
                'subject' => 'We received your message',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">
    Thank you, {{ $name }}!
</h1>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    We have received your message and our team will get back to you as soon as possible. We typically respond within 1–2 business days.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:13px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">
                Your message
            </p>
            <p style="margin:0; font-size:14px; color:#0a1628; line-height:1.7;">
                {{ $description }}
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#eff6ff; border-left:4px solid #0d5fbf; border-radius:0 8px 8px 0; padding:16px 20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                What happens next?
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                A member of our team will review your message and respond to
                <strong>{{ $email }}</strong> within 1–2 business days.
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="'.$link.'"
               target="_blank"
               style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">
                Visit NTI Platform
            </a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    This is an automated confirmation — please do not reply to this email.<br>
    For direct contact reach us at
    <a href="mailto:info@nti.sk" style="color:#0d5fbf; text-decoration:none;">info@nti.sk</a>
</p>
',
            ],
            [
                'slug' => 'admin_notification_organization_onboarded',
                'subject' => 'New organization awaiting approval: {{ $organizationName }}',
                'body_html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New organization awaiting approval</title>
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; padding:40px 0;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                <!-- Header -->


                <!-- Content -->
                <tr>
                    <td style="padding:40px; color:#0a1628;">

                        <h1 style="margin:0 0 8px; font-size:24px;">
                            New organization awaiting approval
                        </h1>

                        <p style="margin:0 0 28px; font-size:15px; color:#64748b; line-height:1.6;">
                            A new organization has registered in the system and is waiting for administrative review.
                        </p>

                        <!-- Organization details -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">

                            <tr>
                                <td style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0; font-size:13px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">
                                        Organization details
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:0 20px;">

                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8; width:140px;">Name</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628; font-weight:600;">{{ $organizationName }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Company ID</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $ico }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Sector</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $sector }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Address</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $address }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Contact email</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $contactEmail }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Registered at</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $registeredAt }}</td>
                                        </tr>

                                    </table>

                                </td>
                            </tr>

                        </table>

                        <!-- Button -->
                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
                                    <a href="'.$link.'/admin/organizations/{{ $organizationId }}"
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
                                        Review Organization
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
                            This is an automated admin notification. Please log in to the admin panel to approve or reject this organization.
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px; text-align:center; font-size:12px; color:#94a3b8; background:#f1f5f9;">
                        © {{ date(\'Y\') }} Nitriansky Technický Inkubátor
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
',
            ],
            [
                'slug' => 'organization_account_approved',
                'subject' => 'Welcome aboard, {{ $organizationName }}!',

                'body_html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Approved</title>
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; padding:40px 0;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                <tr>
                    <td style="padding:40px; color:#0a1628;">

                        <h1 style="margin:0 0 16px; font-size:24px;">
                            Welcome aboard, {{ $organizationName }}! 🎉
                        </h1>

                        <p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
                            Your organization has been approved by the NTI team. You now have full access to the platform.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
                            <tr>
                                <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
                                    <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">
                                        Here’s what you can do now:
                                    </p>
                                    <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                                        📢 Post projects and opportunities<br>
                                        🎓 Connect with talented students<br>
                                        👥 Build and manage your team<br>
                                        🌐 Grow your presence in the NTI ecosystem
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
                                    <a href="'.$link.'/firma"
                                       target="_blank"
                                       style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">
                                        Go to Dashboard
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin-top:30px; font-size:12px; color:#94a3b8;">
                            If you have any questions, contact our support team anytime.
                        </p>

                    </td>
                </tr>

                <tr>
                    <td style="padding:20px; text-align:center; font-size:12px; color:#94a3b8; background:#f1f5f9;">
                        © {{ date(\'Y\') }} Nitriansky Technický Inkubátor
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
',
            ],
            [
                'slug' => 'team_invite',
                'subject' => 'Team invitation: {{ $teamName }}',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Team invitation</h1>
<p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
    <strong>{{ $inviterName }}</strong> invited you to join <strong>{{ $teamName }}</strong> on the NTI platform.
</p>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Sign in with <strong>{{ $inviteeEmail }}</strong> and accept the invitation using the button below.
</p>
<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $joinUrl }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Accept invitation</a>
        </td>
    </tr>
</table>
<p style="margin-top:24px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If the button does not work, copy this link:<br>
    <a href="{{ $joinUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $joinUrl }}</a>
</p>',
            ],
            [
                'slug'    => 'commission_member_invite',
                'subject' => 'You have been invited to join the NTI evaluation commission',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Commission invitation</h1>
<p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
    You have been invited as an evaluator to the commission
    <strong style="color:#0a1628;">{{ $commissionName }}</strong>
    on the NTI platform.
</p>
<p style="margin:0 0 24px; font-size:14px; color:#64748b; line-height:1.6;">
    Click the button below to set your password and submit your account for approval by the NTI administrator.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:16px 20px;">
            <p style="margin:0; font-size:13px; color:#64748b;">
                Your login e-mail: <strong style="color:#0a1628;">{{ $inviteeEmail }}</strong>
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $acceptUrl }}" target="_blank"
               style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">
                Accept invitation
            </a>
        </td>
    </tr>
</table>

<p style="margin-top:24px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If the button does not work, copy this link:<br>
    <a href="{{ $acceptUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $acceptUrl }}</a>
</p>
<p style="margin-top:16px; font-size:12px; color:#94a3b8;">
    This invitation expires in 72 hours.
    If you received this message by mistake, please contact the NTI administrator.
</p>',
            ],
            [
                'slug'    => 'organization_member_invite',
                'subject' => 'You have been invited to join {{ $organizationName }} on NTI',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Organization invitation 🎉</h1>
<p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
    <strong>{{ $organizationName }}</strong> has invited you to join the NTI platform
    as <strong>{{ $roleLabel }}</strong>.
</p>
<p style="margin:0 0 24px; font-size:14px; color:#64748b; line-height:1.6;">
    Click the button below to set your password and submit your account for NTI admin approval.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:16px 20px;">
            <p style="margin:0; font-size:13px; color:#64748b;">
                Your login email: <strong style="color:#0a1628;">{{ $inviteeEmail }}</strong>
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $acceptUrl }}" target="_blank"
               style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">
                Accept invitation
            </a>
        </td>
    </tr>
</table>

<p style="margin-top:24px; font-size:12px; color:#94a3b8; line-height:1.5;">
    If the button does not work, copy this link:<br>
    <a href="{{ $acceptUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $acceptUrl }}</a>
</p>
<p style="margin-top:16px; font-size:12px; color:#94a3b8;">
    This invitation expires in 72 hours.
    If you received this by mistake, please contact the organisation administrator.
</p>',
            ],
            [
                'slug'      => 'application_status_changed',
                'subject'   => 'Application {{ $applicationRef }} status update',
                'type'      => 'transactional',
                'is_active' => true,
                'body_html' => '<h2 style="margin:0 0 8px; font-size:22px; font-weight:600; color:#0a1628;">
    Hi, {{ $userName }}!
</h2>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    The status of your application has changed. See the details below.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f0fdf4; border-left:4px solid #16a34a; border-radius:8px; padding:20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                Application: {{ $applicationRef }}
            </p>
            <p style="margin:0 0 4px; font-size:14px; color:#64748b;">
                Call: <strong>{{ $callName }}</strong>
            </p>
            <p style="margin:0 0 4px; font-size:14px; color:#64748b;">
                New status: <strong style="color:#0a1628;">{{ $newStatus }}</strong>
            </p>
        </td>
    </tr>
</table>

<p style="margin:0 0 16px; font-size:14px; color:#64748b; line-height:1.6;">
    {{ $note }}
</p>

<p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
    If you have any questions, contact us at
    <a href="mailto:support@nti.sk" style="color:#64748b;">support@nti.sk</a>.
</p>',
            ],
            [
                'slug'      => 'call_pending_approval',
                'subject'   => 'Call "{{ $callName }}" is pending approval',
                'type'      => 'transactional',
                'is_active' => true,
                'body_html' => '<h2 style="margin:0 0 8px; font-size:22px; font-weight:600; color:#0a1628;">
    Hi, {{ $userName }}!
</h2>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    A new call has been submitted for approval. Please review it and approve or reject it.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#fff7ed; border-left:4px solid #f97316; border-radius:8px; padding:20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                Call pending approval
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                Name: <strong>{{ $callName }}</strong>
            </p>
        </td>
    </tr>
</table>

<p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
    Please log in to the admin portal and process this request.
</p>',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
