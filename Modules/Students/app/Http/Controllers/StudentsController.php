<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $validated = $request->validate([
            'honor_declaration' => ['required', 'boolean'],
            'transcript_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $transcriptPath = $student->academicRecord?->transcript_file ?? null;
        if ($request->hasFile('transcript_file')) {
            /** @var UploadedFile $file */
            $file = $request->file('transcript_file');
            $filename = sprintf('%s_%s.%s', $student->id, uniqid('', true), $file->getClientOriginalExtension());
            $transcriptPath = $file->storeAs('transcripts', $filename, 'local');
        }

        $academicRecord = AcademicRecord::updateOrCreate(
            ['student_id' => $student->id],
            [
                'transcript_file' => $transcriptPath,
                'honor_declaration' => $validated['honor_declaration'],
                'honor_declaration_signed_at' => $validated['honor_declaration'] ? now() : null,
            ]
        );

        return response()->json([
            'academic_record' => $academicRecord,
        ], Response::HTTP_OK);
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
                'studyProgram',
                'studyField',
                'university',
                'studyYear',
                'academicFlags',
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
