<?php

namespace Modules\Students\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Students\Models\Student;

class StudentsController extends Controller
{
    use AuthorizesRequests;

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
            'academicFlags',
        ])->get();

        return response()->json([
            'students' => $students,
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Student::class);

        $validated = $request->validate([
            'study_program_id' => ['required', 'integer', 'exists:study_program,id'],
            'study_field_id'   => ['required', 'integer', 'exists:study_field,id'],
            'university_id'    => ['required', 'integer', 'exists:university,id'],
            'year_of_study'    => ['required', 'integer', 'min:1', 'max:6'],
            'portfolio_url'    => ['nullable', 'url', 'max:255'],
            'academic_flags'   => ['nullable', 'array'],
            'academic_flags.*' => ['integer', 'exists:academic_flags,id'],
        ]);

        $userId = $request->user()->id;

        if (Student::where('user_id', $userId)->exists()) {
            return response()->json([
                'message' => 'Študentský profil pre tohto používateľa už existuje.',
            ], Response::HTTP_CONFLICT);
        }

        $student = DB::transaction(function () use ($validated, $userId) {
            $student = Student::create([
                'user_id'          => $userId,
                'study_program_id' => $validated['study_program_id'],
                'study_field_id'   => $validated['study_field_id'],
                'university_id'    => $validated['university_id'],
                'year_of_study'    => $validated['year_of_study'],
                'portfolio_url'    => $validated['portfolio_url'] ?? null,
            ]);

            if (!empty($validated['academic_flags'])) {
                $student->academicFlags()->attach($validated['academic_flags']);
            }

            return $student;
        });

        return response()->json([
            'message' => 'Študentský profil bol úspešne vytvorený.',
            'student' => $student->load('studyProgram', 'studyField', 'university', 'academicFlags'),
        ], Response::HTTP_CREATED);
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
                'academicFlags',
            ]),
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('students::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'study_program_id' => ['sometimes', 'integer', 'exists:study_program,id'],
            'study_field_id'   => ['sometimes', 'integer', 'exists:study_field,id'],
            'university_id'    => ['sometimes', 'integer', 'exists:university,id'],
            'year_of_study'    => ['sometimes', 'integer', 'min:1', 'max:6'],
            'portfolio_url'    => ['nullable', 'url', 'max:255'],
            'academic_flags'   => ['nullable', 'array'],
            'academic_flags.*' => ['integer', 'exists:academic_flags,id'],
        ]);

        DB::transaction(function () use ($validated, $student) {
            $student->update(collect($validated)->except('academic_flags')->toArray());

            if (array_key_exists('academic_flags', $validated)) {
                $student->academicFlags()->sync($validated['academic_flags'] ?? []);
            }
        });

        return response()->json([
            'message' => 'Študentský profil bol úspešne aktualizovaný.',
            'student' => $student->fresh()->load('studyProgram', 'studyField', 'university', 'academicFlags'),
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
