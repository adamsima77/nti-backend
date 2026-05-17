<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\SiteMember;

class SiteMemberController extends Controller
{
    use AuthorizesRequests;

    /**
     * CMS listing
     */
    public function index()
    {
        $siteMembers = SiteMember::with('cmsStatus')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($siteMembers, Response::HTTP_OK);
    }

    /**
     * Public listing
     */
    public function fetchByLang()
    {
        $siteMembers = SiteMember::orderByDesc('created_at')
            ->paginate(15);
        return response()->json($siteMembers, Response::HTTP_OK);
    }

    public function fetchByLangPublic()
    {
        $siteMembers = SiteMember::where('status_id', 1)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($siteMembers, Response::HTTP_OK);
    }



    /**
     * CMS single item
     */
    public function showCms(int $id)
    {
        $siteMember = SiteMember::with('cmsStatus')
            ->find($id);

        if (!$siteMember) {
            return response()->json([
                'message' => 'Site member not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($siteMember, Response::HTTP_OK);
    }

    /**
     * Public single item
     */
    public function show(int $id)
    {
        $siteMember = SiteMember::where('status_id', 1)
            ->find($id);

        if (!$siteMember) {
            return response()->json([
                'message' => 'Site member not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($siteMember, Response::HTTP_OK);
    }

    /**
     * Store new member
     */
    public function store(Request $request)
    {
        $this->authorize('create', SiteMember::class);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'job_position' => ['nullable', 'string', 'max:255'],
            'status_id'    => ['nullable', 'exists:cms_statuses,id'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        $imagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')
                    ->store('site-members', 'public');
            }

            SiteMember::create([
                'name'         => $validated['name'],
                'job_position' => $validated['job_position'] ?? null,
                'image'        => $imagePath,
                'status_id'    => $validated['status_id'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Site member created'
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {

            DB::rollBack();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'message' => 'Site member could not be created',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update member
     */
    public function update(Request $request, int $id)
    {
        $siteMember = SiteMember::findOrFail($id);

        $this->authorize('update', $siteMember);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'job_position' => ['nullable', 'string', 'max:255'],
            'status_id'    => ['nullable', 'exists:cms_statuses,id'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        try {
            DB::beginTransaction();

            $memberData = [
                'name'         => $validated['name'],
                'job_position' => $validated['job_position'] ?? null,
                'status_id'    => $validated['status_id'] ?? $siteMember->status_id,
            ];

            if ($request->hasFile('image')) {

                if ($siteMember->image) {
                    Storage::disk('public')->delete($siteMember->image);
                }

                $memberData['image'] = $request->file('image')
                    ->store('site-members', 'public');
            }

            $siteMember->update($memberData);

            DB::commit();

            return response()->json([
                'message' => 'Site member updated'
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Site member could not be updated',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete member
     */
    public function destroy(int $id)
    {
        $siteMember = SiteMember::findOrFail($id);

        $this->authorize('delete', $siteMember);

        try {
            DB::beginTransaction();

            if ($siteMember->image) {
                Storage::disk('public')->delete($siteMember->image);
            }

            $siteMember->delete();

            DB::commit();

            return response()->json([
                'message' => 'Site member deleted'
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Site member could not be deleted',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
