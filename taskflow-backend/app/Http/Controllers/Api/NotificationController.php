<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Obtener notificaciones del usuario
     * GET /api/v1/notifications
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->with(['task', 'flow'])
            ->orderBy('created_at', 'desc');

        // Filtrar por leídas/no leídas
        if ($request->has('unread') && $request->unread) {
            $query->unread();
        }

        // Filtrar por tipo
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'unread_count' => Notification::where('user_id', $request->user()->id)
                    ->unread()
                    ->count(),
            ],
        ], 200);
    }

    /**
     * Marcar notificación como leída
     * PUT /api/v1/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
        ], 200);
    }

    /**
     * Marcar todas como leídas
     * POST /api/v1/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas',
        ], 200);
    }

    /**
     * Eliminar notificación
     * DELETE /api/v1/notifications/{id}
     */
    public function destroy($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada',
        ], 200);
    }

    /**
     * Obtener estadísticas de notificaciones
     * GET /api/v1/notifications/stats
     */
    public function stats(Request $request)
    {
        $userId = $request->user()->id;

        $stats = [
            'total' => Notification::where('user_id', $userId)->count(),
            'unread' => Notification::where('user_id', $userId)->unread()->count(),
            'urgent' => Notification::where('user_id', $userId)->unread()->urgent()->count(),
            'by_type' => Notification::where('user_id', $userId)
                ->unread()
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }
}