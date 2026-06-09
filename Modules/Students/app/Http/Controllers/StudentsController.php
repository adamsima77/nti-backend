<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Applications\Models\SecurityClassification;
use Modules\Content\Models\Language;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\AcademicRecord;
use Modules\Students\Models\Student;

class StudentsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Študentský záznam prihláseného používateľa (ak existuje).
     */
    public function showMe(Request $request)
    {
        $langCode =
            $request->cookie('i18n_redirected')
            ?? $request->header('accept-language')
            ?? 'sk';

        $langId = Language::where('name', $langCode)->value('id');

        $student = Student::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'user',
                'studyProgram.studyProgramTranslations' => function ($q) use ($langId) {
                    $q->where('language_id', $langId);
                },
                'studyField.studyFieldTranslations' => function ($q) use ($langId) {
                    $q->where('language_id', $langId);
                },
                'university',
                'studyYear.studyYearTranslations' => function ($q) use ($langId) {
                    $q->where('language_id', $langId);
                },
                'academicFlags',
            ])
            ->first();

        if ($student === null) {
            return response()->json(['student' => null], Response::HTTP_OK);
        }

        $this->authorize('view', $student);

        return response()->json([
            'student' => $student,
            'lang'    => $langId,
        ], Response::HTTP_OK);
    }

    /**
     * Store or update the authenticated student's academic record.
     */
    public function storeAcademicRecord(Request $request)
    {
        $student = $request->user()?->student;
        if ($student === null) {
            return response()->json([
                'message' => 'Študentský profil nebol nájdený.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $student);

        // Check if a transcript already exists to adjust validation rules
        $hasExistingDocument = $student->academicRecord?->transcript_file !== null;

        $validated = $request->validate([
            'honor_declaration' => ['required', 'boolean', 'accepted'],
            'transcript_file' => $hasExistingDocument
                ? ['nullable', 'file', 'mimes:pdf', 'max:5120']
                : ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        // Use a transaction to ensure database and storage stay in sync
        return DB::transaction(function () use ($request, $student, $validated, $hasExistingDocument) {

            // 1. If a new file is uploaded, replace the old one (GDPR compliance)
            if ($request->hasFile('transcript_file')) {
                if ($hasExistingDocument) {
                    $oldDoc = Document::find($student->academicRecord->transcript_file);

                    if ($oldDoc) {
                        // Delete actual files from disk
                        foreach ($oldDoc->versions as $version) {
                            Storage::disk('local')->delete($version->file_path);
                        }
                        // Cascade delete will automatically remove entries in document_version
                        $oldDoc->delete();
                    }
                }

                // 2. Process the new file
                $uploadedTranscript = $request->file('transcript_file');
                $storedName = Str::uuid() . '_' . $uploadedTranscript->getClientOriginalName();
                $filePath = $uploadedTranscript->storeAs('documents', $storedName, 'local');

                $securityClassification = SecurityClassification::where('name', 'internal')->firstOrFail();

                // 3. Create new document and version records
                $doc = Document::create([
                    'owner_id'                   => $request->user()->id,
                    'security_classification_id' => $securityClassification->id,
                ]);

                DocumentVersion::create([
                    'document_id' => $doc->id,
                    'file_name'   => $uploadedTranscript->getClientOriginalName(),
                    'file_path'   => $filePath,
                ]);

                // 4. Update the AcademicRecord with the new document ID
                $student->academicRecord()->updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'transcript_file'             => $doc->id,
                        'honor_declaration'           => $validated['honor_declaration'],
                        'honor_declaration_signed_at' => now(),
                    ]
                );
            } else {
                // If just updating the declaration without a new file
                $student->academicRecord()->updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'honor_declaration'           => $validated['honor_declaration'],
                        'honor_declaration_signed_at' => now(),
                    ]
                );
            }

            return response()->json([
                'message' => 'Academic record processed successfully!',
            ], Response::HTTP_OK);
        });
    }

    public function downloadRecord(Request $request, Document $document)
    {
        $doc = Document::with([
            'securityClassification',
            'versions' => function ($query) {

                $query->latest();
            }
        ])->findOrFail($document->id);


        if ((int)$doc->owner_id !== (int)$request->user()->id) {
            return response()->json([
                'message' => 'Nemáte oprávnenie na stiahnutie tohto dokumentu.'
            ], Response::HTTP_FORBIDDEN);
        }


        $latestVersion = $doc->versions->first();

        if (!$latestVersion || !$latestVersion->file_path) {
            return response()->json([
                'message' => 'Súbor dokumentu sa nenašiel.'
            ], Response::HTTP_NOT_FOUND);
        }


        if (!Storage::disk('local')->exists($latestVersion->file_path)) {
            return response()->json([
                'message' => 'Fyzický súbor na úložisku chýba.'
            ], Response::HTTP_NOT_FOUND);
        }


        return Storage::disk('local')->download(
            $latestVersion->file_path,
            $latestVersion->file_name
        );
    }

    /**
     * Display the authenticated student's academic record.
     */
    public function academicRecordMe(Request $request)
    {
        $student = $request->user()?->student;
        if ($student === null) {
            return response()->json([
                'message' => 'Študentský profil nebol nájdený.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $student);

        return response()->json([
            'academic_record' => $student->loadMissing('academicRecord')->academicRecord,
        ], Response::HTTP_OK);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::with([
            'user',
            'studyProgram',
            'studyField',
            'university',
            'studyYear',
            'academicFlags',
        ])->get();

        return response()->json([
            'students' => $students,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Self-registration flow: no user_id needed — creates the profile for
     * the authenticated user via the hasOne relation.
     *
     * Admin flow: called internally from UserController after creating the
     * user, passing the new User model directly — no user_id from frontend.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Student::class);

        $validated = $request->validate([
            'study_program_id' => ['required', 'integer'],
            'study_field_id'   => ['nullable', 'integer'],
            'university_id'    => ['nullable', 'integer'],
            'study_year_id'    => ['nullable', 'integer'],
            'portfolio_url'    => ['nullable', 'url', 'max:255'],
            'academic_flags'   => ['nullable', 'array'],
            'academic_flags.*' => ['integer'],
        ]);

        $user = $request->user();

        if ($user->student()->exists()) {
            return response()->json([
                'message' => 'Študentský profil pre tohto používateľa už existuje.',
            ], Response::HTTP_CONFLICT);
        }

        $student = $this->createStudentForUser($user, $validated);

        return response()->json([
            'message' => 'Študentský profil bol úspešne vytvorený.',
            'student' => $student->load('studyProgram', 'studyField', 'university', 'studyYear', 'academicFlags'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Create a student profile for a given User model.
     * Used both by store() (self-registration) and UserController (admin flow).
     * No user_id is ever taken from request input.
     */
    public function createForUser(User $user, array $data): Student
    {
        return $this->createStudentForUser($user, $data);
    }

    private function createStudentForUser(User $user, array $data): Student
    {
        return DB::transaction(function () use ($user, $data) {
            // Create via relation — user_id is set automatically by Eloquent
            $student = $user->student()->create([
                'study_program_id' => $data['study_program_id'],
                'study_field_id'   => $data['study_field_id']  ?? null,
                'university_id'    => $data['university_id']   ?? null,
                'study_year_id'    => $data['study_year_id']   ?? null,
                'portfolio_url'    => $data['portfolio_url']   ?? null,
            ]);

            if (!empty($data['academic_flags'])) {
                $student->academicFlags()->attach($data['academic_flags']);
            }

            return $student;
        });
    }

    /**
     * Show the specified resource.
     */
    public function show(Student $student)
    {
        $this->authorize('view', $student);

        return response()->json([
            'student' => $student->load([
                'user',
                'studyProgram.studyProgramTranslations',
                'studyField.studyFieldTranslations',
                'university',
                'studyYear.studyYearTranslations',
                'academicFlags',
                'academicRecord',
            ]),
        ], Response::HTTP_OK);
    }

    /**
     * Fetch the academic record for a student.
     */
    public function academicRecord(Student $student)
    {
        $this->authorize('view', $student);

        return response()->json([
            'academic_record' => $student->loadMissing('academicRecord')->academicRecord,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'study_program_id' => ['sometimes', 'integer'],
            'study_field_id'   => ['sometimes', 'nullable', 'integer'],
            'university_id'    => ['sometimes', 'nullable', 'integer'],
            'study_year_id'    => ['sometimes', 'nullable', 'integer'],
            'portfolio_url'    => ['nullable', 'url', 'max:255'],
            'cv_document_id'   => ['nullable', 'integer'],
            'academic_flags'   => ['nullable', 'array'],
            'academic_flags.*' => ['integer'],
        ]);

        DB::transaction(function () use ($validated, $student) {
            $student->update(collect($validated)->except('academic_flags')->toArray());

            if (array_key_exists('academic_flags', $validated)) {
                $student->academicFlags()->sync($validated['academic_flags'] ?? []);
            }
        });

        return response()->json([
            'message' => 'Študentský profil bol úspešne aktualizovaný.',
            'student' => $student->fresh()->load('studyProgram', 'studyField', 'university', 'studyYear', 'academicFlags'),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);

        $student->academicFlags()->detach();
        $student->delete();

        return response()->json([
            'message' => 'Študentský profil bol úspešne odstránený.',
        ], Response::HTTP_OK);
    }
}
