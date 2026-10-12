<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::with('room')->where('status', 'active')->each(function (Tenant $tenant) {
            if (! $tenant->room) {
                return;
            }

            $currentMonth = now()->startOfMonth();
            $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

            $lastMonthBill = Bill::updateOrCreate(
                ['tenant_id' => $tenant->id, 'billing_month' => $lastMonth],
                [
                    'room_id' => $tenant->room_id,
                    'room_rate' => $tenant->room->monthly_rate,
                    'electricity_amount' => 650,
                    'water_amount' => 250,
                    'other_charges' => 0,
                    'total_amount' => (float) $tenant->room->monthly_rate + 900,
                    'due_date' => $lastMonth->copy()->addDays(9),
                    'status' => 'pending',
                ]
            );

            Payment::updateOrCreate(
                ['bill_id' => $lastMonthBill->id, 'reference_number' => 'GCASH-'.$lastMonthBill->id],
                [
                    'tenant_id' => $tenant->id,
                    'payment_date' => $lastMonth->copy()->addDays(5),
                    'amount' => $tenant->email === 'tenant@example.com' ? $lastMonthBill->total_amount : 1000,
                    'payment_method' => 'GCash',
                    'remarks' => 'Seed payment record',
                ]
            );

            $lastMonthBill->updatePaymentStatus();

            $currentMonthBill = Bill::updateOrCreate(
                ['tenant_id' => $tenant->id, 'billing_month' => $currentMonth],
                [
                    'room_id' => $tenant->room_id,
                    'room_rate' => $tenant->room->monthly_rate,
                    'electricity_amount' => 500,
                    'water_amount' => 200,
                    'other_charges' => 100,
                    'total_amount' => (float) $tenant->room->monthly_rate + 800,
                    'due_date' => $currentMonth->copy()->addDays(9),
                    'status' => 'pending',
                ]
            );

            $currentMonthBill->updatePaymentStatus();
        });

        Bill::where('due_date', '<', Carbon::today())->where('status', '!=', 'paid')->get()->each->updatePaymentStatus();
    }
}
