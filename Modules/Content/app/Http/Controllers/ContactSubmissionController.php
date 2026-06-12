<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Events\ContactMessageSubmitted;
use Modules\Content\Models\ContactSubmission;
use Modules\IdentityAccess\Models\ConsentType;
use Modules\IdentityAccess\Models\UserConsent;
use Modules\IdentityAccess\Rules\TurnstileRule;

class ContactSubmissionController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ContactSubmission::class);

        $contact_submissions = ContactSubmission::orderByDesc('created_at')->paginate(15);

        return response()->json($contact_submissions, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'surname'               => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255'],
            'description'           => ['required', 'string', 'max:2500'],
            'consent'               => ['required', 'accepted'],
            'cf_turnstile_response' => ['required', new TurnstileRule()],
        ]);

        $submission = ContactSubmission::create([
            'name'        => $validated['name'],
            'surname'     => $validated['surname'],
            'email'       => $validated['email'],
            'description' => $validated['description'],
            'user_id'     => $request->user()?->id,
        ]);

        if ($request->user()) {
            $consent = ConsentType::where('name', 'contact_form_processing')->firstOrFail();

            UserConsent::create([
                'user_id'    => $request->user()->id,
                'consent_id' => $consent->id,
                'granted'    => true,
                'granted_at' => now(),
                'revoked_at' => null,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }



        $languageId = LanguageType::SLOVAK->value; //Temporary
        event(new ContactMessageSubmitted($submission, $languageId));

        return response()->json(['message' => 'Contact submission created!'], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $contact_submission = ContactSubmission::findOrFail($id);
        $this->authorize('view', $contact_submission);

        return response()->json($contact_submission, Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        $contact_submission = ContactSubmission::findOrFail($id);
        $this->authorize('update', $contact_submission);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'surname'     => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255'],
            'description' => ['required', 'string', 'max:2500'],
        ]);

        $contact_submission->update([
            'name'        => $validated['name'],
            'surname'     => $validated['surname'],
            'email'       => $validated['email'],
            'description' => $validated['description'],
        ]);

        return response()->json(['message' => 'Contact submission updated!'], Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $contact_submission = ContactSubmission::findOrFail($id);
        $this->authorize('delete', $contact_submission);
        $contact_submission->delete();

        return response()->json(['message' => 'Contact submission deleted!'], Response::HTTP_OK);
    }
}
