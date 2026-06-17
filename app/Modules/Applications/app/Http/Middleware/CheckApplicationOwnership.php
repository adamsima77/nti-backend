<?php

namespace Modules\Applications\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Applications\Models\Applications;
use Symfony\Component\HttpFoundation\Response;

class CheckApplicationOwnership
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $applicationId = $request->route('application') ?? $request->route('applications');

        if ($applicationId) {
            $application = Applications::find($applicationId);

            if (!$application) {
                return response()->json(['message' => 'Application not found'], 404);
            }

            if ($application->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized to access this application'], 403);
            }
        }

        return $next($request);
    }
}
