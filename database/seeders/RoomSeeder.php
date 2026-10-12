<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['room_number' => '1', 'floor' => 1, 'type' => 'single', 'capacity' => 1, 'monthly_rate' => 4500, 'status' => 'available'],
            ['room_number' => '2', 'floor' => 1, 'type' => 'double', 'capacity' => 2, 'monthly_rate' => 3500, 'status' => 'available'],
            ['room_number' => '3', 'floor' => 2, 'type' => 'shared', 'capacity' => 4, 'monthly_rate' => 2500, 'status' => 'available'],
            ['room_number' => '4', 'floor' => 2, 'type' => 'double', 'capacity' => 2, 'monthly_rate' => 3800, 'status' => 'available'],
            ['room_number' => '5', 'floor' => 3, 'type' => 'single', 'capacity' => 1, 'monthly_rate' => 5000, 'status' => 'under maintenance'],
            ['room_number' => '6', 'floor' => 5, 'type' => 'shared', 'capacity' => 6, 'monthly_rate' => 3000, 'status' => 'available'],
        ];

        // Rooms 7-12: single rooms at PHP 4,600.
        foreach (range(7, 12) as $number) {
            $rooms[] = ['room_number' => (string) $number, 'floor' => (int) ceil($number / 4), 'type' => 'single', 'capacity' => 1, 'monthly_rate' => 4600, 'status' => 'available'];
        }

        foreach ($rooms as $room) {
            Room::updateOrCreate(['room_number' => $room['room_number']], array_merge([
                'electricity_rate' => Setting::number('electricity_rate'),
                'water_rate' => Setting::number('water_rate'),
            ], $room));
        }
    }
}
