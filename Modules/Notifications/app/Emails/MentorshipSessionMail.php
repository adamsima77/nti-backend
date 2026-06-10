<?php

namespace Modules\Notifications\Emails;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Mentorship\Models\MentorshipSession;
use Modules\Notifications\Models\EmailTemplate;

class MentorshipSessionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MentorshipSession $mentorshipSession,
        public string $fullName,
        public int $languageId = 1 // Pridal som podľa tvojho vzoru, ak využívate preklady
    ) {}

    public function build(): self
    {
        $session = $this->mentorshipSession;

        // Vytiahnutie mena mentora
        $mentor = $session->mentorship?->mentor;
        $mentorName = $mentor ? $mentor->name . ' ' . $mentor->surname : 'Tvoj mentor';

        // Formátovanie dátumu priamo do čistého stringu
        $formattedDate = $session->scheduled_at
            ? Carbon::parse($session->scheduled_at)->format('d.m.Y H:i')
            : '';

        // Nastavenie textu formy stretnutia a odkazu na hovor
        $formaText = $session->type === 'online' ? '🌐 Online stretnutie' : '📍 Osobne (Offline)';
        $meetingUrlText = ($session->type === 'online' && !empty($session->meeting_url))
            ? $session->meeting_url
            : 'Pre detail prejdite na portál';

        // Ošetrenie agendy (ak je prázdna, pošle sa iba čistý prázdny string)
        $agendaTitle = !empty($session->agenda) ? 'Agenda / Poznámky:' : '';
        $agendaBody = !empty($session->agenda) ? $session->agenda : '';

        // Nastavenie odkazu pre hlavné tlačidlo (ak je online, hodí ho priamo na hovor, inak na dashboard)
        $actionURL = ($session->type === 'online' && !empty($session->meeting_url))
            ? $session->meeting_url
            : url('/dashboard');

        $callName = $session->mentorship?->application?->call?->name ?? 'Program';
        // Príprava čistých dát pre tvoj renderovací engine
        $data = [
            'fullName'       => $this->fullName,
            'mentorName'     => $mentorName,
            'callName'       => $callName,
            'title'          => $session->title,
            'duration'       => $session->duration,
            'formattedDate'  => $formattedDate,
            'formaText'      => $formaText,
            'meetingUrlText' => $meetingUrlText,
            'agendaTitle'    => $agendaTitle,
            'agendaBody'     => $agendaBody,
            'actionURL'      => $actionURL,
        ];

        // Vyhľadanie šablóny (rovnaký princíp ako tvoj onboarded príklad)
        $template = EmailTemplate::findBySlug('mentorship_session_created');

        if ($this->languageId) {
            $template = $template?->forLanguage($this->languageId);
        }

        if (!$template) {
            return $this->subject('Nová mentorska konzultácia')
                ->view('notifications::emails.layout')
                ->with(['body_html' => '']);
        }

        $renderedSubject = $template->renderSubject($data);
        $renderedBody    = $template->render($data);

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
