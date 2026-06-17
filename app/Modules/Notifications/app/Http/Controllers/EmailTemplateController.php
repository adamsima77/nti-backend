<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Models\EmailTemplate;

class EmailTemplateController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $templates = EmailTemplate::with('translations')
            ->orderBy('slug')
            ->paginate(15);

        return response()->json($templates, Response::HTTP_OK);
    }

    public function fetchAll(){
        $this->authorize('fetchAll', EmailTemplate::class);
        $templates = EmailTemplate::where('type', 'bulk')->get();
        return response()->json($templates, Response::HTTP_OK);
    }

    public function show($id)
    {
        $template = EmailTemplate::with('translations')->findOrFail($id);

        return response()->json($template, Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        $template = EmailTemplate::with('translations')->findOrFail($id);
        $this->authorize('update', $template);

        $validated = $request->validate([
            'language_id' => ['required', 'exists:languages,id'],
            'subject'     => ['required', 'string', 'max:255'],
            'body_html'   => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $translation = $template->translations()
                ->where('language_id', $validated['language_id'])
                ->first();

            if ($translation) {
                $translation->update([
                    'subject'   => $validated['subject'],
                    'body_html' => $validated['body_html'],
                ]);
            } else {
                $template->translations()->create([
                    'language_id' => $validated['language_id'],
                    'subject'     => $validated['subject'],
                    'body_html'   => $validated['body_html'],
                ]);
            }

            // Keep base model columns in sync when saving EN (language_id = 2)
            if ((int) $validated['language_id'] === 2) {
                $template->update([
                    'subject'   => $validated['subject'],
                    'body_html' => $validated['body_html'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Email template updated successfully!'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Email template could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchByLang(string $lang)
    {
        $languageId = \Modules\Content\Models\Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json(['message' => 'Language not found!'], Response::HTTP_NOT_FOUND);
        }

        $templates = EmailTemplate::with([
            'translations' => fn($q) => $q->where('language_id', $languageId),
            'category',
        ])
            ->orderBy('slug')
            ->paginate(15);

        return response()->json($templates, Response::HTTP_OK);
    }

    public function showCms(int $id)
    {
        $template = EmailTemplate::with([
            'translations',
            'category',
        ])->find($id);

        if (!$template) {
            return response()->json(['message' => 'Email template not found!'], Response::HTTP_NOT_FOUND);
        }

        // Normalize: ensure EN (id=2) always exists in translations,
        // falling back to the base model columns (which are always EN).
        $hasEn = $template->translations->contains('language_id', 2);

        if (!$hasEn) {
            $template->translations->push(new \Modules\Notifications\Models\EmailTemplateTranslation([
                'language_id' => 2,
                'subject'     => $template->subject,
                'body_html'   => $template->body_html,
            ]));
        }

        return response()->json($template, Response::HTTP_OK);
    }
}
