<?php

namespace Modules\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Applications\Models\Application;
use Modules\Evaluation\Models\CommissionMember;

class EvaluationController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $commissionMemberIds = CommissionMember::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        if ($commissionMemberIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $applications = Application::query()
            ->select([
                'application.id',
                'application.call_id',
                'application.team_id',
                'application.created_by',
                'application.submitted_at',
                'application.created_at',
            ])
            ->with([
                'call:id,name',
                'creator:id,name,surname,email',
            ])
            ->whereNotNull('application.submitted_at')
            ->whereNotExists(function ($query) use ($commissionMemberIds) {
                $query->selectRaw('1')
                    ->from('evaluation')
                    ->whereColumn('evaluation.application_id', 'application.id')
                    ->whereIn('evaluation.commission_member_id', $commissionMemberIds);
            })
            ->latest('application.id')
            ->paginate($perPage);

        return response()->json($applications);
    }
}