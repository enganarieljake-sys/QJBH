<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class TenantConcernController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->tenant($request);

        $concerns = $tenant
            ? Concern::where('tenant_id', $tenant->id)->latest()->paginate(10)
            : collect();

        return view('tenant.concerns.index', [
            'tenant' => $tenant,
            'concerns' => $concerns,
            'approvalPending' => ! $request->user()->isApprovedTenant(),
        ]);
    }

    public function create(Request $request)
    {
        if (! $this->tenant($request)) {
            return redirect()->route('tenant.concerns.index')
                ->with('warning', 'Your account must be approved and linked to a tenant record before sending a concern.');
        }

        return view('tenant.concerns.create', ['categories' => Concern::CATEGORIES]);
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant($request);

        if (! $tenant) {
            return redirect()->route('tenant.concerns.index')
                ->with('warning', 'Your account must be approved and linked to a tenant record before sending a concern.');
        }

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Concern::CATEGORIES))],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $concern = Concern::create($data + ['tenant_id' => $tenant->id, 'status' => 'open']);

        Notification::send(User::staff(['admin', 'caretaker']), new ActivityNotification([
            'title' => 'New tenant concern',
            'message' => $tenant->full_name.' sent a concern: '.$concern->subject,
            'url' => route('admin.concerns.show', $concern),
            'kind' => 'concern',
        ]));

        return redirect()
            ->route('tenant.concerns.show', $concern)
            ->with('success', 'Your concern was sent to the admin.');
    }

    public function show(Request $request, Concern $concern)
    {
        $tenant = $this->tenant($request);

        abort_if(! $tenant || (int) $concern->tenant_id !== (int) $tenant->id, 403);

        return view('tenant.concerns.show', compact('concern'));
    }

    private function tenant(Request $request)
    {
        return $request->user()->isApprovedTenant() ? $request->user()->tenant : null;
    }
}
