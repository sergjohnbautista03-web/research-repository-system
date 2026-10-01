<?php

namespace App\Http\Controllers;

use App\Services\NotificationFeed;
use Illuminate\Http\Request;

class DeanNotificationController extends Controller
{
    public function index(Request $request)
    {
        $dean = $request->user();
        abort_unless($dean->isDepartmentDean() && filled($dean->department), 403);

        return response()->json(app(NotificationFeed::class)->snapshot($dean))
            ->header('Cache-Control', 'private, no-store');
    }
}
