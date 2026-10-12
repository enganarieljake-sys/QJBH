<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::with('author')
                ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['title', 'body']))
                ->forBoard()
                ->paginate(10)
                ->withQueryString(),
            'tenantCount' => $this->tenantRecipients()->count(),
        ]);
    }

    public function create()
    {
        return view('admin.announcements.create', ['announcement' => new Announcement()]);
    }

    public function store(Request $request)
    {
        $announcement = new Announcement($this->validated($request));
        $announcement->user_id = $request->user()->id;
        $announcement->save();

        $tenants = $this->tenantRecipients()->get();
        Notification::send($tenants, new ActivityNotification([
            'title' => 'New announcement: '.Str::limit($announcement->title, 60),
            'message' => Str::limit($announcement->body, 120),
            'url' => route('tenant.announcements.index', absolute: false).'#announcement-'.$announcement->id,
            'kind' => 'announcement',
        ]));

        $staff = User::whereIn('role', User::STAFF_ROLES)->get();
        Notification::send($staff, new ActivityNotification([
            'title' => 'Announcement posted: '.Str::limit($announcement->title, 60),
            'message' => Str::limit($announcement->body, 120),
            'url' => route('notifications.index', absolute: false),
            'kind' => 'announcement',
        ]));

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement posted. '.$tenants->count().' '.Str::plural('tenant', $tenants->count()).' and '.$staff->count().' staff notified; subscribed phones also received a push.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $data['is_pinned'] = $request->boolean('is_pinned');

        return $data;
    }

    private function tenantRecipients()
    {
        return User::where('role', 'tenant')->whereNotNull('tenant_approved_at');
    }
}
