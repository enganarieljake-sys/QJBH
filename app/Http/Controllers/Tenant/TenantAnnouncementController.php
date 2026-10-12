<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class TenantAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        // Opening the board clears any unread announcement notifications.
        $request->user()->unreadNotifications()
            ->where('data', 'like', '%"kind":"announcement"%')
            ->update(['read_at' => now()]);

        return view('tenant.announcements.index', [
            'announcements' => Announcement::with('author')->forBoard()->paginate(10),
        ]);
    }
}
