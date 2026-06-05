<?php

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\AuditCompliance\Enums\EventType;
use Modules\AuditCompliance\Enums\GdprReportStatus;
use Modules\AuditCompliance\Enums\SeverityType;
use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\AuditCompliance\Models\GdprReport;
use Modules\AuditCompliance\Models\SystemEvent;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Models\Organization;
use Modules\Reporting\Policies\SuperAdminDashboardPolicy;

class SuperAdminDashboardController extends Controller
{
    use AuthorizesRequests;
    public function fetchAllUsersCount(Request $request){
        $this->authorize("fetchAllUsersCount", SuperAdminDashboardPolicy::class);
        $users = User::where("status_id", "!=", UserStatus::ANONYMIZED)->count();
        return response()->json(['count' => $users], Response::HTTP_OK);
    }

    //Fetch logs for last 48 hours
public function fetchLogs(Request $request)
    {
        $this->authorize('fetchLogs', SuperAdminDashboardPolicy::class);

        $perPage = (int) $request->input('per_page', 15);
        $page    = (int) $request->input('page', 1);


        $systemEvents = SystemEvent::with('user:id,email')
            ->whereIn('event_type', [EventType::SYSTEM_ERROR, EventType::SECURITY_ALERT])
            ->where('created_at', '>', now()->subHours(48))->get()
            ->map(fn(SystemEvent $e) => (object) [
                'id'          => $e->id,
                'source'      => 'system_event',
                'type'        => $e->event_type,
                'severity'    => $e->severity,
                'message'     => $e->message,
                'action'      => null,
                'object_type' => null,
                'object_id'   => null,
                'ip'          => $e->ip_address,
                'created_at'  => $e->created_at,
                'user'        => $e->user
                    ? ['id' => $e->user->id, 'email' => $e->user->email]
                    : null,
            ]);

        $auditEvents = AuditCompliance::with('actor:id,email')
            ->where('time_of_action', '>', now()->subHours(48))->get()
            ->map(fn(AuditCompliance $e) => (object) [
                'id'          => $e->id,
                'source'      => 'audit_event',
                'type'        => 'AUDIT',
                'severity'    => $e->result,
                'message'     => $e->action,
                'action'      => $e->action,
                'object_type' => $e->object_type,
                'object_id'   => $e->object_id,
                'ip'          => $e->ip,
                'created_at'  => $e->time_of_action,
                'user'        => $e->actor
                    ? ['id' => $e->actor->id, 'email' => $e->actor->email]
                    : null,
            ]);


        $merged = $systemEvents
            ->merge($auditEvents)
            ->sortByDesc('created_at')
            ->values();

        $paginated = new LengthAwarePaginator(
            items:       $merged->slice(($page - 1) * $perPage, $perPage)->values(),
            total:       $merged->count(),
            perPage:     $perPage,
            currentPage: $page,
            options:     [
                'path'  => $request->url(),
                'query' => $request->query(),
            ],
        );

        return response()->json($paginated);
    }

    public function fetchAllLogs(Request $request)
    {
        $this->authorize('fetchLogs', SuperAdminDashboardPolicy::class);

        $perPage = (int) $request->input('per_page', 15);
        $page    = (int) $request->input('page', 1);

        $year = $request->filled('year')
            ? (int) $request->input('year')
            : null;

        $day = $request->input('day');

        $timeFrom = $request->input('time_from');
        $timeTo   = $request->input('time_to');

        $applyFilters = function (
            $query,
            string $column
        ) use (
            $request,
            $year,
            $day,
            $timeFrom,
            $timeTo
        ) {

            if ($request->filled('year')) {
                $query->whereYear($column, $year);
            }

            if ($request->filled('day')) {
                $query->whereDate($column, $day);
            }

            if ($request->filled('time_from')) {
                $query->whereTime($column, '>=', $timeFrom);
            }

            if ($request->filled('time_to')) {
                $query->whereTime($column, '<=', $timeTo);
            }

            return $query;
        };


        $systemEventsQuery = SystemEvent::with('user:id,email')
            ->whereIn('event_type', [
                EventType::SYSTEM_ERROR,
                EventType::SECURITY_ALERT,
            ]);

        $applyFilters($systemEventsQuery, 'created_at');

        $systemEvents = $systemEventsQuery
            ->get()
            ->map(fn (SystemEvent $e) => (object) [
                'id'          => $e->id,
                'source'      => 'system_event',
                'type'        => $e->event_type,
                'severity'    => $e->severity,
                'message'     => $e->message,
                'action'      => null,
                'object_type' => null,
                'object_id'   => null,
                'ip'          => $e->ip_address,
                'created_at'  => $e->created_at,
                'user'        => $e->user
                    ? [
                        'id'    => $e->user->id,
                        'email' => $e->user->email,
                    ]
                    : null,
            ]);

        $auditEventsQuery = AuditCompliance::with('actor:id,email');

        $applyFilters($auditEventsQuery, 'time_of_action');

        $auditEvents = $auditEventsQuery
            ->get()
            ->map(fn (AuditCompliance $e) => (object) [
                'id'          => $e->id,
                'source'      => 'audit_event',
                'type'        => 'AUDIT',
                'severity'    => $e->result,
                'message'     => $e->action,
                'action'      => $e->action,
                'object_type' => $e->object_type,
                'object_id'   => $e->object_id,
                'ip'          => $e->ip,
                'created_at'  => $e->time_of_action,
                'user'        => $e->actor
                    ? [
                        'id'    => $e->actor->id,
                        'email' => $e->actor->email,
                    ]
                    : null,
            ]);


        $merged = $systemEvents
            ->merge($auditEvents)
            ->sortByDesc('created_at')
            ->values();

        $paginated = new LengthAwarePaginator(
            items: $merged
                ->slice(($page - 1) * $perPage, $perPage)
                ->values(),
            total: $merged->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path'  => $request->url(),
                'query' => $request->query(),
            ],
        );

        return response()->json($paginated);
    }

    public function fetchAllOrganizationsCount(Request $request){
        $this->authorize("fetchAllOrganizationsCount", SuperAdminDashboardPolicy::class);
        $organizations = Organization::count();
        return response()->json(['count' => $organizations], Response::HTTP_OK);
    }

    public function securityAlertsNewer()
    {
        $this->authorize("fetchActiveErrors", SuperAdminDashboardPolicy::class);

        $count = SystemEvent::query()
            ->whereIn('event_type', [EventType::SYSTEM_ERROR, EventType::SECURITY_ALERT])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }

    public function activeSystemProblemsCount()
    {
        $this->authorize("fetchActiveErrors", SuperAdminDashboardPolicy::class);
        $count = SystemEvent::query()
            ->where('event_type', EventType::SYSTEM_ERROR)
            ->whereIn('severity', [SeverityType::CRITICAL, SeverityType::ERROR])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }

    public function fetchGdprPrune()
    {
        $this->authorize("fetchGdprPrune", SuperAdminDashboardPolicy::class);

        // Exports done, cron hasn't anonymized them yet
        $awaitingAnonymization = GdprReport::where('status', GdprReportStatus::COMPLETED->value)
            ->count();

        // Already pruned by cron
        $deletedExpiredRecords = GdprReport::where('status', GdprReportStatus::EXPIRED->value)
            ->count();

        // Last time cron actually pruned something
        $lastRun = GdprReport::where('status', GdprReportStatus::EXPIRED->value)
            ->latest('updated_at')
            ->value('updated_at');

        $recentFailures = GdprReport::where('status', GdprReportStatus::FAILED->value)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        return response()->json([
            'automaticCleaning'      => true,
            'lastRun'                => $lastRun ? Carbon::parse($lastRun)->format('Y-m-d H:i:s') : '—',
            'status'                 => $recentFailures ? 'WARNING' : 'OK',
            'awaitingAnonymization'  => $awaitingAnonymization,
            'deletedExpiredRecords'  => $deletedExpiredRecords,
        ]);
    }

    public function fetchStatusOfServices()
    {
        $this->authorize("fetchStatusOfServices", SuperAdminDashboardPolicy::class);
        $services = [];

        // Redis
        try {
            Redis::ping();
            $services['redis'] = [
                'status'  => 'OK',
                'message' => 'Redis connection healthy',
            ];
        } catch (\Exception $e) {
            $services['redis'] = [
                'status'  => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }

        // Cache
        try {
            $testKey = 'cache_health_check_' . now()->timestamp;
            Cache::put($testKey, 'healthy', 10);
            $value = Cache::get($testKey);

            if ($value !== 'healthy') {
                throw new \Exception('Cache write/read mismatch');
            }

            Cache::forget($testKey);

            $services['cache'] = [
                'status'  => 'OK',
                'message' => 'Cache working properly',
            ];
        } catch (\Exception $e) {
            $services['cache'] = [
                'status'  => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }

        try {
            $queueName   = config('queue.connections.redis.queue', 'default');
            $pendingJobs = Redis::llen("queues:{$queueName}");
            $failedJobs  = DB::table('failed_jobs')->count();

            $services['queue'] = [
                'status'       => $failedJobs > 0 ? 'WARNING' : 'OK',
                'pending_jobs' => $pendingJobs,
                'failed_jobs'  => $failedJobs,
                'message'      => $failedJobs > 0
                    ? "{$failedJobs} failed job(s) detected"
                    : 'Queue healthy',
            ];
        } catch (\Exception $e) {
            $services['queue'] = [
                'status'  => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }

        $lastRunAt = Cache::get('scheduler:gdpr_retention:last_run');
        $lastRun   = $lastRunAt ? Carbon::parse($lastRunAt) : null;
        $isHealthy = $lastRun?->greaterThan(now()->subMinutes(10)) ?? false;

        $services['scheduler'] = [
            'status'   => $isHealthy ? 'OK' : 'WARNING',
            'last_run' => $lastRun?->toDateTimeString() ?? 'Never',
            'message'  => $isHealthy
                ? 'Scheduler running normally'
                : 'Scheduler may not be running',
        ];

        $statuses      = collect($services)->pluck('status');
        $overallStatus = match (true) {
            $statuses->contains('ERROR')   => 'ERROR',
            $statuses->contains('WARNING') => 'WARNING',
            default                        => 'OK',
        };

        return response()->json([
            'overall_status' => $overallStatus,
            'services'       => $services,
            'checked_at'     => now(),
        ]);
    }
}
