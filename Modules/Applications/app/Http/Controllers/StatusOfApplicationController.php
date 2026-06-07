<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Applications\Models\StatusOfApplication;

class StatusOfApplicationController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statuses = StatusOfApplication::all();
        return response()->json(['statuses' => $statuses], Response::HTTP_OK);
    }

    public function fetchExceptDraftAdmin(){
        $statuses = StatusOfApplication::where('name', "!=", "Draft")->get();
        return response()->json(['statuses' => $statuses], Response::HTTP_OK);
    }

    public function fetchAdminStatuses(){
        $statuses = StatusOfApplication::whereIn('name',[
            'V hodnotení',
            'Vyžiadané doplnenie',
            'Aktívny projekt',
            'Ukončené',
            'Pozastavené',
            'Onboarding'
        ])->get();

        return response()->json(['statuses' => $statuses], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

        return response()->json([]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        //

        return response()->json([]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //

        return response()->json([]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //

        return response()->json([]);
    }
}
