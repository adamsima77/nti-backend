<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Notifications\Models\EmailTemplateTranslation;

class EmailTemplateTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $link = config('app.frontend_url');
        $translations = [
            'organization_onboarded' => [
                'subject'   => 'Ďakujeme, {{ $organizationName }}!',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Ďakujeme, {{ $organizationName }}! 🎉</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Vaša organizácia bola úspešne zaregistrovaná na platforme NTI.
    Náš tím skontroluje vašu žiadosť a čoskoro sa vám ozve.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#fef9c3; border-left:4px solid #eab308; border-radius:8px; padding:20px;">
            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#0a1628;">
                ⏳ Účet čaká na schválenie
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                Váš účet je momentálne v procese overovania. Prístup na platformu získate po schválení naším tímom. Zvyčajne to trvá 1-2 pracovné dni.
            </p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Čo bude nasledovať:</p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                🔍 Tím NTI skontroluje údaje vašej organizácie<br>
                ✅ Dostanete potvrdzovací e-mail o schválení<br>
                🚀 Získate plný prístup na platformu
            </p>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Ak máte akékoľvek otázky, neváhajte kontaktovať náš tím podpory.
</p>',
            ],
            'student_onboarded' => [
                'subject'   => 'Si tu, {{ $userName }}!',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Si tu, {{ $userName }}! 🎓</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Váš študentský profil bol úspešne vytvorený. Máte plný prístup na platformu NTI a všetko čo ponúka.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Čo môžete robiť teraz:</p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                🚀 Prezerať a prihlasovať sa na projekty<br>
                🤝 Spojiť sa s mentormi a partnermi<br>
                📁 Prezentovať svoje portfólio<br>
                🏆 Vstúpiť do tímov a súťaží
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="'.$link.'/student" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Prejsť na dashboard</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Ak máte akékoľvek otázky, neváhajte kontaktovať náš tím podpory.
</p>',
            ],
            'welcome_email' => [
                'subject'   => 'Vitajte v NTI, {{ $userName }}!',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Vitajte v NTI, {{ $userName }} 👋</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Váš e-mail bol úspešne overený. Zostáva už len jeden krok pred tým, ako začnete objavovať všetko čo Nitriansky Technický Inkubátor ponúka.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">Dokončite registráciu v 2 jednoduchých krokoch:</p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                ✅ Krok 1 — Overenie e-mailu<br>
                🔲 Krok 2 — Dokončenie onboarding profilu
            </p>
        </td>
    </tr>
</table>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Povedzte nám niečo o sebe — či ste študent hľadajúci príležitosti alebo organizácia pripravená spolupracovať, nastavíme vás za pár minút.
</p>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="'.$link.'/auth/onboarding" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Dokončiť onboarding</a>
        </td>
    </tr>
</table>',
            ],
            'verify_email' => [
                'subject'   => 'Overte svoju e-mailovú adresu',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Overte svoju e-mailovú adresu</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Ďakujeme za registráciu v Nitrianskom Technickom Inkubátore. Potvrďte svoj e-mail a aktivujte účet.
</p>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $verificationUrl }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Overiť e-mail</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Ak tlačidlo nefunguje, skopírujte a vložte tento odkaz:<br>
    <a href="{{ $verificationUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $verificationUrl }}</a>
</p>',
            ],
            'reset_password' => [
                'subject'   => 'Obnovenie hesla',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Obnovenie hesla</h1>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Ahoj {{ $userName }},<br><br>
    Dostali sme žiadosť o obnovenie vášho hesla. Kliknite na tlačidlo nižšie a nastavte nové heslo.
</p>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Obnoviť heslo</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Ak tlačidlo nefunguje, skopírujte a vložte tento odkaz:<br>
    <a href="{{ $url }}" style="color:#0d5fbf; word-break:break-all;">{{ $url }}</a>
</p>
<p style="margin-top:20px; font-size:12px; color:#94a3b8;">
    Ak ste o obnovenie hesla nežiadali, ignorujte tento e-mail.
</p>',
            ],
            'password_changed' => [
                'subject'   => 'Bezpečnostné upozornenie — zmena hesla',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Bezpečnostné upozornenie</h1>
<p style="margin:0 0 20px; font-size:15px; color:#64748b; line-height:1.6;">Vaše heslo k účtu NTI bolo úspešne zmenené.</p>
<p style="margin:0 0 16px; font-size:14px; color:#64748b;">Ahoj {{ $userEmail }},</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:14px; border-radius:10px;">
            <div style="font-size:12px; color:#64748b;">Účet</div>
            <div style="font-weight:600; color:#0a1628;">{{ $userEmail }}</div>
        </td>
    </tr>
</table>

<p style="margin:0 0 12px; font-size:14px; color:#0a1628;">Ak ste túto zmenu vykonali vy, môžete tento e-mail ignorovať.</p>
<p style="margin:0; font-size:14px; color:#0a1628;">Ak ste heslo NEMENILI, okamžite ho obnovte alebo kontaktujte podporu.</p>',
            ],
            'milestone_status_changed' => [
                'subject'   => 'Zmena stavu míľnika: {{ $milestoneName }}',
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
            <a href="'.$link.'/projekt/{{ $projectId }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Zobraziť projekt</a>
        </td>
    </tr>
</table>',
            ],
            'organization_member_invite' => [
                'subject'   => 'Organizácia {{ $organizationName }} vás pozýva na NTI platformu',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Pozvánka do organizácie 🎉</h1>
<p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
    Organizácia <strong>{{ $organizationName }}</strong> vás pozýva stať sa členom NTI platformy
    v roli <strong>{{ $roleLabel }}</strong>.
</p>
<p style="margin:0 0 24px; font-size:14px; color:#64748b; line-height:1.6;">
    Kliknite na tlačidlo nižšie, nastavte si heslo a váš účet bude odoslaný na schválenie NTI administrátorom.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:16px 20px;">
            <p style="margin:0; font-size:13px; color:#64748b;">
                Váš prihlasovací e-mail: <strong style="color:#0a1628;">{{ $inviteeEmail }}</strong>
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $acceptUrl }}" target="_blank"
               style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">
                Prijať pozvánku
            </a>
        </td>
    </tr>
</table>

<p style="margin-top:24px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Ak tlačidlo nefunguje, skopírujte tento odkaz:<br>
    <a href="{{ $acceptUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $acceptUrl }}</a>
</p>
<p style="margin-top:16px; font-size:12px; color:#94a3b8;">
    Platnosť pozvánky vyprší o 72 hodín.
    Ak ste pozvánku nedostali správne, kontaktujte administrátora organizácie.
</p>',
            ],
            'team_invite' => [
                'subject'   => 'Pozvánka do tímu: {{ $teamName }}',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">Pozvánka do tímu</h1>
<p style="margin:0 0 16px; font-size:15px; color:#64748b; line-height:1.6;">
    <strong>{{ $inviterName }}</strong> vás pozval/a do tímu <strong>{{ $teamName }}</strong> na platforme NTI.
</p>
<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Prihláste sa pod účtom <strong>{{ $inviteeEmail }}</strong> a potvrďte pripojenie kliknutím na tlačidlo nižšie.
</p>
<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
            <a href="{{ $joinUrl }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Prijať pozvánku</a>
        </td>
    </tr>
</table>
<p style="margin-top:24px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Ak tlačidlo nefunguje, skopírujte odkaz:<br>
    <a href="{{ $joinUrl }}" style="color:#0d5fbf; word-break:break-all;">{{ $joinUrl }}</a>
</p>',
            ],
            [
                'slug' => 'contact_message_received_sk',
                'subject' => 'Dostali sme vašu správu',
                'body_html' => '
<h1 style="margin:0 0 16px; font-size:24px;">
    Ďakujeme, {{ $name }}!
</h1>

<p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
    Vašu správu sme úspešne prijali a náš tím sa vám ozve čo najskôr. Zvyčajne odpovedáme do 1–2 pracovných dní.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
    <tr>
        <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
            <p style="margin:0 0 12px; font-size:13px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">
                Vaša správa
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
                Čo bude nasledovať?
            </p>
            <p style="margin:0; font-size:14px; color:#64748b; line-height:1.6;">
                Člen nášho tímu skontroluje vašu správu a odpovie na
                <strong>{{ $email }}</strong> do 1–2 pracovných dní.
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" bgcolor="#0d5fbf" style="border-radius:8px;">
             <a href="'.$link.'" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">
             Navštíviť NTI platformu</a>
        </td>
    </tr>
</table>

<p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
    Toto je automatické potvrdenie — prosím neodpovedajte na tento email.<br>
    Pre priamy kontakt nás môžete osloviť na
    <a href="mailto:info@nti.sk" style="color:#0d5fbf; text-decoration:none;">info@nti.sk</a>
</p>
',
            ],
            [
                'slug' => 'admin_notification_organization_onboarded',
                'subject' => 'Nová organizácia čaká na schválenie: {{ $organizationName }}',
                'body_html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nová organizácia čaká na schválenie</title>
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; padding:40px 0;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                <!-- Header -->
                <tr>
                    <td style="padding:30px; text-align:center; background:#0a1628;">
                        <img src="{{ $message->embed(public_path(\'emails/nti-logo.png\')) }}"
                             alt="NTI Logo"
                             style="height:50px;">
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:40px; color:#0a1628;">

                        <h1 style="margin:0 0 8px; font-size:24px;">
                            Nová organizácia čaká na schválenie
                        </h1>

                        <p style="margin:0 0 28px; font-size:15px; color:#64748b; line-height:1.6;">
                            V systéme sa zaregistrovala nová organizácia a čaká na administrátorské overenie.
                        </p>

                        <!-- Organization details -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">

                            <tr>
                                <td style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0; font-size:13px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">
                                        Detaily organizácie
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:0 20px;">

                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8; width:140px;">Názov</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628; font-weight:600;">{{ $organizationName }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">IČO</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $ico }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Sektor</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $sector }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Adresa</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $address }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Kontaktný email</td>
                                            <td style="padding:14px 0; font-size:14px; color:#0a1628;">{{ $contactEmail }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding:14px 0; font-size:14px; color:#94a3b8;">Dátum registrácie</td>
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
                                        Skontrolovať organizáciu
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin-top:30px; font-size:12px; color:#94a3b8; line-height:1.5;">
                            Toto je automatické administrátorské upozornenie. Prosím prihláste sa do administrácie a vykonajte kontrolu.
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
                'subject' => 'Vitajte na palube, {{ $organizationName }}!',

                'body_html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Účet schválený</title>
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; padding:40px 0;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                <tr>
                    <td style="padding:30px; text-align:center; background:#0a1628;">
                        <img src="{{ $message->embed(public_path(\'emails/nti-logo.png\')) }}"
                             alt="NTI Logo"
                             style="height:50px;">
                    </td>
                </tr>

                <tr>
                    <td style="padding:40px; color:#0a1628;">

                        <h1 style="margin:0 0 16px; font-size:24px;">
                            Vitajte na palube, {{ $organizationName }}! 🎉
                        </h1>

                        <p style="margin:0 0 24px; font-size:15px; color:#64748b; line-height:1.6;">
                            Vaša organizácia bola schválená tímom NTI. Teraz máte plný prístup do platformy.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
                            <tr>
                                <td style="background:#f1f5f9; border-radius:8px; padding:20px;">
                                    <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#0a1628;">
                                        Čo môžete teraz robiť:
                                    </p>
                                    <p style="margin:0; font-size:14px; color:#64748b; line-height:2;">
                                        📢 Zverejňovať projekty a príležitosti<br>
                                        🎓 Prepájať sa so študentmi<br>
                                        👥 Budovať a riadiť tím<br>
                                        🌐 Rozvíjať svoju prítomnosť v NTI ekosystéme
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
                                        Prejsť do dashboardu
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin-top:30px; font-size:12px; color:#94a3b8;">
                            V prípade otázok kontaktujte náš support tím.
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
            ]
        ];

        foreach ($translations as $slug => $data) {
            $template = EmailTemplate::where('slug', $slug)->first();

            if (!$template) {
                continue;
            }

            EmailTemplateTranslation::updateOrCreate(
                [
                    'email_template_id' => $template->id,
                    'language_id'       => 1,
                ],
                $data
            );
        }
    }
}
