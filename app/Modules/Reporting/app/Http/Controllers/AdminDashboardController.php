<?php

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Reporting\Policies\AdminDashboardPolicy;
use Modules\Teams\Models\Team;

class AdminDashboardController extends Controller
{
    use AuthorizesRequests;

    public function fetchApplicationsCount(Request $request)
    {
        $this->authorize('fetchApplicationsCount', AdminDashboardPolicy::class);

        $count = Application::count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }

    public function fetchUsersCount(Request $request)
    {
        $this->authorize('fetchUsersCount', AdminDashboardPolicy::class);

        $count = User::where('status_id', '!=', UserStatus::ANONYMIZED)->count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }

    public function fetchTeamCount(Request $request)
    {
        $this->authorize('fetchTeamCount', AdminDashboardPolicy::class);

        $count = Team::count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }

    public function fetchActiveCalls(Request $request)
    {
        $this->authorize('fetchActiveCalls', AdminDashboardPolicy::class);

        $activeCalls = Call::with([
            'currentStatusHistory.status'
        ])
            ->withCount('applications')
            ->whereHas('currentStatusHistory', function ($query) {
                $query->where('status_of_call_id', 2);
            })
            ->latest()
            ->limit(6)
            ->get();

        return response()->json($activeCalls, Response::HTTP_OK);
    }

    public function fetchPendingApprovalOrganizations(Request $request)
    {
        $this->authorize('fetchPendingApprovalOrganizations', AdminDashboardPolicy::class);


        $organizations = User::with(['organizations'])
            ->where('status_id', UserStatus::PENDING_APPROVAL->value)
            ->latest()
            ->limit(5)
            ->get();

        return response()->json($organizations, Response::HTTP_OK);
    }

    public function fetchActiveCallsCount(Request $request)
    {
        $this->authorize('fetchActiveCallsCount', AdminDashboardPolicy::class);

        $count = Call::whereHas('statusHistory', function ($query) {
            $query->where('status_of_call_id', 2);
        })->count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }
}
