<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Applications\Models\SecurityClassification;
use Modules\Mentorship\Models\Milestone;
use Modules\Programs\Models\Call;

class MilestoneDocumentController extends Controller
{

    public function index(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        abort_if($milestone->call_id !== $call->id, 404);

        $docs = $milestone->documents()->get()->map(fn ($doc) => $this->formatDocument($doc));

        return response()->json(['documents' => $docs]);
    }

    public function store(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        abort_if($milestone->call_id !== $call->id, 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx,doc,xlsx,xls,pptx,ppt,zip,png,jpg,jpeg', 'max:20480'],
        ]);

        $user = $request->user();

        $document = DB::transaction(function () use ($request, $user, $milestone) {
            $classification = SecurityClassification::first()
                ?? SecurityClassification::create(['name' => 'Interné']);

            $uploadedFile = $request->file('file');
            $fileName     = $uploadedFile->getClientOriginalName();
            $storedName   = Str::uuid()->toString() . '_' . $fileName;
            $filePath     = Storage::disk('local')->putFileAs('documents', $uploadedFile, $storedName);

            $document = Document::create([
                'owner_id'                  => $user->id,
                'security_classification_id' => $classification->id,
            ]);

            DocumentVersion::create([
                'document_id' => $document->id,
                'file_name'   => $fileName,
                'file_path'   => $filePath,
            ]);

            $milestone->documents()->attach($document->id);

            return $document->load('versions');
        });

        return response()->json($this->formatDocument($document), 201);
    }

    public function download(Request $request, Call $call, Milestone $milestone, Document $document)
    {
        abort_if($milestone->call_id !== $call->id, 404);
        abort_unless($milestone->documents()->where('document_id', $document->id)->exists(), 404);

        $version = $document->versions()->latest('id')->firstOrFail();

        abort_unless(Storage::disk('local')->exists($version->file_path), 404);

        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }

    public function destroy(Request $request, Call $call, Milestone $milestone, Document $document): JsonResponse
    {
        abort_if($milestone->call_id !== $call->id, 404);
        abort_unless($milestone->documents()->where('document_id', $document->id)->exists(), 404);

        $user = $request->user();
        abort_unless(
            $document->owner_id === $user->id || $user->hasRole(['admin', 'superadmin']),
            403
        );

        DB::transaction(function () use ($milestone, $document) {
            $milestone->documents()->detach($document->id);

            $hasOtherLinks = DB::table('document_has_milestone')
                ->where('document_id', $document->id)
                ->exists()
                || DB::table('document_has_application')
                ->where('document_id', $document->id)
                ->exists();

            if (! $hasOtherLinks) {
                foreach ($document->versions as $version) {
                    Storage::disk('local')->delete($version->file_path);
                }
                $document->delete();
            }
        });

        return response()->json(['message' => 'Dokument bol odstránený.']);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatDocument(Document $doc): array
    {
        $latest = $doc->versions->sortByDesc('id')->first();

        return [
            'id'         => $doc->id,
            'file_name'  => $latest?->file_name,
            'uploaded_at' => $latest?->created_at?->toDateTimeString(),
            'owner_id'   => $doc->owner_id,
        ];
    }
}
