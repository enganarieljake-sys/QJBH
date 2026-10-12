<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Notifications\ActivityNotification;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConcernController extends Controller
{
    public function index(Request $request)
    {
        $concerns = Concern::with('tenant.room')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['subject', 'message', 'tenant.full_name']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.concerns.index', [
            'concerns' => $concerns,
            'statuses' => Concern::STATUSES,
            'categories' => Concern::CATEGORIES,
            'filters' => $request->only(['status', 'category']),
        ]);
    }

    public function show(Concern $concern)
    {
        $concern->load('tenant.room');

        return view('admin.concerns.show', [
            'concern' => $concern,
            'statuses' => Concern::STATUSES,
        ]);
    }

    public function update(Request $request, Concern $concern)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Concern::STATUSES)],
            'admin_reply' => ['nullable', 'string', 'max:3000'],
        ]);

        $replyChanged = ($data['admin_reply'] ?? null) !== $concern->admin_reply;
        $statusChanged = $data['status'] !== $concern->status;

        $concern->fill($data);
        if ($replyChanged && filled($data['admin_reply'] ?? null)) {
            $concern->replied_at = now();
        }
        $concern->save();

        if ($replyChanged || $statusChanged) {
            $concern->load('tenant.user');
            $concern->tenant?->user?->notify(new ActivityNotification([
                'title' => $replyChanged && filled($concern->admin_reply) ? 'Admin replied to your concern' : 'Concern updated',
                'message' => '"'.$concern->subject.'" is now '.str_replace('-', ' ', $concern->status).'.',
                'url' => route('tenant.concerns.show', $concern),
                'kind' => 'concern',
            ]));
        }

        return redirect()
            ->route('admin.concerns.show', $concern)
            ->with('success', 'Concern updated and the tenant was notified.');
    }

    public function destroy(Concern $concern)
    {
        $concern->delete();

        return redirect()->route('admin.concerns.index')->with('success', 'Concern deleted.');
    }
}
