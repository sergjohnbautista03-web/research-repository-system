<?php

namespace App\Http\Controllers;

use App\Services\NotificationFeed;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationFeed $feed)
    {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        return response()->json($feed->snapshot($request->user(), page: $data['page'] ?? 1))
            ->header('Cache-Control', 'private, no-store');
    }

    public function markRead(Request $request, NotificationFeed $feed)
    {
        $data = $request->validate(['id' => ['required', 'string', 'max:255']]);
        $events = $feed->events($request->user());
        $event = $events->firstWhere('id', $data['id']);
        abort_unless($event, 404);
        $feed->markRead($request->user(), collect([$event]));

        return response()->json($feed->snapshot($request->user(), $events))->header('Cache-Control', 'private, no-store');
    }

    public function markAllRead(Request $request, NotificationFeed $feed)
    {
        $events = $feed->events($request->user());
        $feed->markRead($request->user(), $events);

        return response()->json($feed->snapshot($request->user(), $events))->header('Cache-Control', 'private, no-store');
    }
}
