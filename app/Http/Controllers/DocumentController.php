<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\SecurityClassification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx'],
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
}
