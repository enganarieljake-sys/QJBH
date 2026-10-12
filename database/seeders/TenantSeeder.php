<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantUser = User::where('email', 'tenant@example.com')->first();

        $sampleTenants = [
            [
                'user_id' => $tenantUser?->id,
                'room' => '1',
                'full_name' => 'Jake Engaña',
                'contact_number' => '09171234567',
                'email' => 'tenant@example.com',
                'emergency_contact' => 'Maria Tenant - 09170000000',
                'valid_id_reference' => 'UMID-2026-0001',
                'move_in_date' => now()->subMonths(3)->toDateString(),
                'status' => 'active',
            ],
            [
                'user_id' => null,
                'room' => '2',
                'full_name' => 'Alex Santos',
                'contact_number' => '09181234567',
                'email' => 'alex.santos@example.com',
                'emergency_contact' => 'Lina Santos - 09181111111',
                'valid_id_reference' => 'DL-2026-0002',
                'move_in_date' => now()->subMonths(2)->toDateString(),
                'status' => 'active',
            ],
            [
                'user_id' => null,
                'room' => '3',
                'full_name' => 'Jamie Cruz',
                'contact_number' => '09191234567',
                'email' => 'jamie.cruz@example.com',
                'emergency_contact' => 'Rico Cruz - 09192222222',
                'valid_id_reference' => 'PASSPORT-0003',
                'move_in_date' => now()->subMonth()->toDateString(),
                'status' => 'reserved',
            ],
        ];

        foreach ($sampleTenants as $tenantData) {
            $room = Room::where('room_number', $tenantData['room'])->first();
            unset($tenantData['room']);

            $tenant = Tenant::updateOrCreate(
                ['email' => $tenantData['email']],
                array_merge($tenantData, ['room_id' => $room?->id])
            );

            $tenant->room?->syncOccupancyStatus();
        }
    }
}
