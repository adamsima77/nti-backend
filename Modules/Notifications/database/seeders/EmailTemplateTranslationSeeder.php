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
            <a href="{{ config(\'app.frontend_url\') }}/student" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Prejsť na dashboard</a>
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
            <a href="{{ config(\'app.frontend_url\') }}/auth/onboarding" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Dokončiť onboarding</a>
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
            <a href="{{ config(\'app.frontend_url\') }}/projekt/{{ $projectId }}" target="_blank" style="display:inline-block; padding:14px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600; border-radius:8px; background-color:#0d5fbf;">Zobraziť projekt</a>
        </td>
    </tr>
</table>',
            ],
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
