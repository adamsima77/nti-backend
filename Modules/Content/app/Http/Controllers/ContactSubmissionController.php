<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Content\Models\ContactSubmission;
use Illuminate\Http\Response;
class ContactSubmissionController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', ContactSubmission::class);
        $contact_submissions = ContactSubmission::orderByDesc('created_at')->paginate(15);
        return response()->json($contact_submissions, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'description' => ['required', 'string', 'max:2500'],
        ]);

        ContactSubmission::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'description' => $validated['description']
        ]);

        return response()->json(['message' => 'Contact submission created !'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $contact_submission = ContactSubmission::findOrFail($id);
        $this->authorize('view', $contact_submission);
        return response()->json($contact_submission, Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {
        $contact_submission = ContactSubmission::findOrFail($id);
        $this->authorize('update', $contact_submission);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'description' => ['required', 'string', 'max:2500']
        ]);

        $contact_submission->update([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'description' => $validated['description']
        ]);

        return response()->json(['message' => 'Contact submission updated !'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $contact_submission = ContactSubmission::findOrFail($id);
        $this->authorize('delete', $contact_submission);
        $contact_submission->delete();
        return response()->json(['message' => 'Contact submission deleted !'], Response::HTTP_OK);
    }
}
