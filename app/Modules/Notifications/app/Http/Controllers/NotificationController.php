<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Events\BulkEmail;
use Modules\Notifications\Models\Notifications;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notifications::class);
        $user = $request->user();

        abort_if($user === null, 401);

        $notifications = Notifications::query()
            ->with('category:id,slug,icon,color')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Notifications $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
                'category' => $notification->category ? [
                    'slug' => $notification->category->slug,
                    'icon' => $notification->category->icon,
                    'color' => $notification->category->color,
                ] : null,
            ]);

        return response()->json($notifications);
    }

    public function sendBulkEmail(Request $request){
        $this->authorize('sendBulkEmail', Notifications::class);
        $validate = $request->validate([
           'call_id' => ['nullable', 'exists:call,id'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'subject' => ['required', 'string', 'max:2000'],
            'email_id' => ['required', 'exists:email_template,id']
        ]);

        event(new BulkEmail(
            $validate['call_id'] ?? null,
            $validate['role_id'] ?? null,
            $validate['subject'],
            $validate['email_id']
        ));

        return response()->json(['message' => 'Email queued !'], Response::HTTP_OK);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $model = Notifications::query()
            ->whereKey($notification)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('markRead', $model);

        $model->markAsRead();

        return response()->json(['message' => 'Notifikácia bola označená ako prečítaná.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $this->authorize('markAllRead', Notifications::class);

        Notifications::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'Všetky notifikácie boli označené ako prečítané.']);
    }
}
