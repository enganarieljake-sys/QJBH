<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use App\Models\MeterReading;
use App\Models\Tenant;
use App\Support\BillReminders;
use App\Support\Search;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Bill::where('due_date', '<', today())
            ->where('status', '!=', 'paid')
            ->get()
            ->each
            ->updatePaymentStatus();

        $bills = Bill::with(['tenant', 'room', 'payments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->tenant_id))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['tenant.full_name', 'room.room_number']))
            ->when($request->filled('billing_month'), function ($query) use ($request) {
                $query->whereDate('billing_month', Carbon::createFromFormat('Y-m', $request->billing_month)->startOfMonth());
            })
            ->latest('billing_month')
            ->paginate(10)
            ->withQueryString();

        return view('admin.bills.index', [
            'bills' => $bills,
            'tenants' => Tenant::orderBy('full_name')->get(),
            'statuses' => Bill::STATUSES,
            'filters' => $request->only(['status', 'tenant_id', 'billing_month']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.bills.create', [
            'bill' => new Bill([
                'billing_month' => now()->startOfMonth(),
                'due_date' => now()->startOfMonth()->addDays(9),
                'electricity_amount' => 0,
                'water_amount' => 0,
                'other_charges' => 0,
            ]),
            'tenants' => Tenant::with('room')->where('status', 'active')->orderBy('full_name')->get(),
            'statuses' => Bill::STATUSES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillRequest $request)
    {
        $bill = DB::transaction(function () use ($request) {
            $tenant = Tenant::with('room')->findOrFail($request->tenant_id);

            if (! $tenant->room || (int) $tenant->room_id !== (int) $request->room_id) {
                abort(422, 'The selected room must match the tenant assigned room.');
            }

            $billingMonth = Carbon::createFromFormat('Y-m', $request->billing_month)->startOfMonth();
            $roomRate = (float) $tenant->room->monthly_rate;
            $total = $roomRate
                + (float) $request->electricity_amount
                + (float) $request->water_amount
                + (float) $request->other_charges;

            $bill = Bill::create([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room_id,
                'billing_month' => $billingMonth,
                'room_rate' => $roomRate,
                'electricity_amount' => $request->electricity_amount,
                'water_amount' => $request->water_amount,
                'other_charges' => $request->other_charges,
                'total_amount' => $total,
                'due_date' => $request->due_date,
                'status' => $request->input('status', 'pending'),
            ]);

            $bill->updatePaymentStatus();

            return $bill;
        });

        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill)
    {
        $bill->load(['tenant', 'room', 'payments' => fn ($query) => $query->latest('payment_date')]);
        $bill->updatePaymentStatus();

        return view('admin.bills.show', compact('bill'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bill $bill)
    {
        $bill->load('tenant.room');

        return view('admin.bills.edit', [
            'bill' => $bill,
            'tenants' => Tenant::with('room')->orderBy('full_name')->get(),
            'statuses' => Bill::STATUSES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillRequest $request, Bill $bill)
    {
        DB::transaction(function () use ($request, $bill) {
            $tenant = Tenant::with('room')->findOrFail($request->tenant_id);

            if (! $tenant->room || (int) $tenant->room_id !== (int) $request->room_id) {
                abort(422, 'The selected room must match the tenant assigned room.');
            }

            $billingMonth = Carbon::createFromFormat('Y-m', $request->billing_month)->startOfMonth();
            $roomRate = (float) $tenant->room->monthly_rate;
            $total = $roomRate
                + (float) $request->electricity_amount
                + (float) $request->water_amount
                + (float) $request->other_charges;

            if ($bill->payments()->sum('amount') > $total) {
                abort(422, 'Total amount cannot be lower than existing payments.');
            }

            $bill->update([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room_id,
                'billing_month' => $billingMonth,
                'room_rate' => $roomRate,
                'electricity_amount' => $request->electricity_amount,
                'water_amount' => $request->water_amount,
                'other_charges' => $request->other_charges,
                'total_amount' => $total,
                'due_date' => $request->due_date,
                'status' => $request->input('status', $bill->status),
            ]);

            $bill->updatePaymentStatus();
        });

        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill)
    {
        if ($bill->payments()->exists()) {
            return back()->with('error', 'Bill cannot be deleted while payments are recorded.');
        }

        $bill->delete();

        return redirect()->route('admin.bills.index')->with('success', 'Bill deleted successfully.');
    }

    public function sendReminders()
    {
        $result = BillReminders::run();

        return back()->with('success', "Reminders sent: {$result['due_soon']} due soon, {$result['overdue']} overdue.");
    }

    public function generateMonthly(Request $request)
    {
        $data = $request->validate([
            'billing_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'electricity_amount' => ['nullable', 'numeric', 'min:0'],
            'water_amount' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
        ]);

        $billingMonth = Carbon::createFromFormat('Y-m', $data['billing_month'])->startOfMonth();
        $created = 0;

        Tenant::with('room')
            ->where('status', 'active')
            ->whereNotNull('room_id')
            ->chunkById(50, function ($tenants) use ($data, $billingMonth, &$created) {
                foreach ($tenants as $tenant) {
                    if (! $tenant->room) {
                        continue;
                    }

                    $exists = Bill::where('tenant_id', $tenant->id)
                        ->whereDate('billing_month', $billingMonth)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $roomRate = (float) $tenant->room->monthly_rate;
                    $electricity = (float) ($data['electricity_amount'] ?? 0);
                    $water = (float) ($data['water_amount'] ?? 0);

                    // A meter reading for the room replaces the flat amounts,
                    // split evenly among the room's active tenants.
                    $reading = MeterReading::where('room_id', $tenant->room_id)->whereDate('billing_month', $billingMonth)->first();
                    if ($reading) {
                        $sharers = max($tenant->room->activeTenants()->count(), 1);
                        $electricity = round((float) $reading->electricity_amount / $sharers, 2);
                        $water = round((float) $reading->water_amount / $sharers, 2);
                    }
                    $other = (float) ($data['other_charges'] ?? 0);

                    $bill = Bill::create([
                        'tenant_id' => $tenant->id,
                        'room_id' => $tenant->room_id,
                        'billing_month' => $billingMonth,
                        'room_rate' => $roomRate,
                        'electricity_amount' => $electricity,
                        'water_amount' => $water,
                        'other_charges' => $other,
                        'total_amount' => $roomRate + $electricity + $water + $other,
                        'due_date' => $data['due_date'],
                        'status' => 'pending',
                    ]);

                    $bill->updatePaymentStatus();
                    $created++;
                }
            });

        return back()->with('success', "{$created} monthly bill(s) generated.");
    }
}
