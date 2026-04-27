<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Programs\Models\Program;

class ProgramsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Program::class);

        return view('programs::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Program::class);

        return view('programs::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Program::class);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $program = Program::query()->findOrFail($id);
        $this->authorize('view', $program);

        return view('programs::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $program = Program::query()->findOrFail($id);
        $this->authorize('update', $program);

        return view('programs::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $program = Program::query()->findOrFail($id);
        $this->authorize('update', $program);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $program = Program::query()->findOrFail($id);
        $this->authorize('delete', $program);
    }
}
