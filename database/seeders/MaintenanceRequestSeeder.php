<?php

namespace Database\Seeders;

use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaintenanceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::where('email', 'tenant@example.com')->first();
        $room = $tenant?->room ?? Room::where('room_number', '1')->first();

        if ($room) {
            MaintenanceRequest::updateOrCreate(
                ['room_id' => $room->id, 'description' => 'Leaking faucet near the comfort room sink.'],
                [
                    'tenant_id' => $tenant?->id,
                    'priority' => 'medium',
                    'status' => 'submitted',
                ]
            );
        }

        $maintenanceRoom = Room::where('status', 'under maintenance')->first();

        if ($maintenanceRoom) {
            MaintenanceRequest::updateOrCreate(
                ['room_id' => $maintenanceRoom->id, 'description' => 'Electrical outlet inspection and replacement.'],
                [
                    'tenant_id' => null,
                    'priority' => 'high',
                    'status' => 'in-progress',
                ]
            );
        }
    }
}
