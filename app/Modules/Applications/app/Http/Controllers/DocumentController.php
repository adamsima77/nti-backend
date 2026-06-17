<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Applications\Models\SecurityClassification;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Document::class);
        $globallyAllowedMimes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'csv'];
        $requestedMimes = $request->input('accept');

        if ($requestedMimes) {
            $requestedArray = explode(',', str_replace(['.', ' '], '', $requestedMimes));
            $safeMimesArray = array_intersect($requestedArray, $globallyAllowedMimes);
            $allowedMimes = !empty($safeMimesArray) ? implode(',', $safeMimesArray) : 'pdf';
        } else {
            $allowedMimes = 'pdf,docx';
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:' . $allowedMimes, 'max:10240'],
        ]);

        $user = $request->user();

        $document = DB::transaction(function () use ($validated, $user) {
            $securityClassification = SecurityClassification::query()->first();

            if ($securityClassification === null) {
                $securityClassification = SecurityClassification::query()->create([
                    'name' => 'Interné',
                ]);
            }

            $uploadedFile = $validated['file'];
            $fileName = $uploadedFile->getClientOriginalName();
            $storedFileName = Str::uuid()->toString().'_'.$fileName;
            $filePath = Storage::disk('local')->putFileAs('documents', $uploadedFile, $storedFileName);

            $document = Document::query()->create([
                'owner_id' => $user->id,
                'security_classification_id' => $securityClassification->id,
            ]);

            DocumentVersion::query()->create([
                'document_id' => $document->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
            ]);

            return $document;
        });

        return response()->json([
            'document_id' => $document->id,
        ], 201);
    }

    public function show(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $latestVersion = $document->versions()->latest('id')->first();

        if (!$latestVersion) {
            return response()->json(['message' => 'No version found for this document'], 404);
        }

        if (!Storage::disk('local')->exists($latestVersion->file_path)) {
            return response()->json(['message' => 'File not found in storage'], 404);
        }

        return response()->json([
            'id' => $document->id,
            'owner_id' => $document->owner_id,
            'security_classification' => $document->securityClassification->name,
            'current_version' => [
                'file_name' => $latestVersion->file_name,
                'file_path' => $latestVersion->file_path,
                'created_at' => $latestVersion->created_at,
            ],
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ]);
    }

    public function download(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $latestVersion = $document->versions()->latest('id')->first();

        if (!$latestVersion) {
            return response()->json(['message' => 'No version found for this document'], 404);
        }

        $filePath = $latestVersion->file_path;

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['message' => 'File not found in storage'], 404);
        }

        return Storage::disk('local')->download(
            $filePath,
            $latestVersion->file_name
        );
    }
}
