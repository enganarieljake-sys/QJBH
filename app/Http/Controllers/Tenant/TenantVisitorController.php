<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;

class TenantVisitorController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->tenant($request);

        return view('tenant.visitors.index', [
            'tenant' => $tenant,
            'visitors' => $tenant
                ? Visitor::where('tenant_id', $tenant->id)->latest('visit_date')->latest('time_in')->paginate(10)
                : collect(),
            'approvalPending' => ! $request->user()->isApprovedTenant(),
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant($request);
        abort_unless($tenant, 403, 'Your tenant account is not active yet.');

        $data = $request->validate([
            'visitor_name' => ['required', 'string', 'max:120'],
            'relationship' => ['nullable', 'string', 'max:60'],
            'purpose' => ['nullable', 'string', 'max:200'],
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'time_in' => ['required', 'date_format:H:i'],
        ]);

        Visitor::create($data + ['tenant_id' => $tenant->id]);

        return redirect()->route('tenant.visitors.index')->with('success', 'Visitor logged. Remember to mark them as left when they go.');
    }

    public function checkout(Request $request, Visitor $visitor)
    {
        $tenant = $this->tenant($request);
        abort_if(! $tenant || (int) $visitor->tenant_id !== (int) $tenant->id, 403);

        if ($visitor->isInside()) {
            $visitor->update(['time_out' => now()->format('H:i')]);
        }

        return back()->with('success', $visitor->visitor_name.' marked as left.');
    }

    private function tenant(Request $request)
    {
        return $request->user()->isApprovedTenant() ? $request->user()->tenant : null;
    }
}
