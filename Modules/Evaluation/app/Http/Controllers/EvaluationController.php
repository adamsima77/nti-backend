<?php

namespace Modules\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\EvaluationScore;

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

    public function storeScore(Request $request, int $applicationId)
    {
        $validated = $request->validate([
            'decision_id'          => ['required', 'integer', 'exists:decision,id'],
            'scores'               => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer', 'exists:criterion,id'],
            'scores.*.score'       => ['required', 'numeric', 'min:0'],
            'scores.*.comment'     => ['required', 'string'],
        ]);

        $commissionMember = CommissionMember::where('user_id', $request->user()->id)->firstOrFail();

        $existingEvaluation = Evaluation::where('application_id', $applicationId)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        if ($existingEvaluation) {
            return response()->json([
                'message' => 'Táto prihláška už bola hodnotená.',
            ], Response::HTTP_CONFLICT);
        }

        $evaluation = DB::transaction(function () use ($validated, $applicationId, $commissionMember) {
            $evaluation = Evaluation::create([
                'application_id'       => $applicationId,
                'commission_member_id' => $commissionMember->id,
                'decision_id'          => $validated['decision_id'],
            ]);

            foreach ($validated['scores'] as $score) {
                EvaluationScore::create([
                    'evaluation_id' => $evaluation->id,
                    'criterion_id'  => $score['criterion_id'],
                    'score'         => $score['score'],
                    'comment'       => $score['comment'],
                ]);
            }

            return $evaluation;
        });

        return response()->json([
            'message'    => 'Hodnotenie bolo úspešne odoslané.',
            'evaluation' => $evaluation->load('scores'),
        ], Response::HTTP_CREATED);
    }
}
