<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monthly electricity (kWh) and water (cubic meter) readings per room.
     * Rates are copied in so old bills stay correct when rates change.
     */
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('billing_month');
            $table->decimal('electricity_previous', 10, 2)->default(0);
            $table->decimal('electricity_current', 10, 2)->default(0);
            $table->decimal('electricity_rate', 10, 2)->default(0);
            $table->decimal('electricity_amount', 10, 2)->default(0);
            $table->decimal('water_previous', 10, 2)->default(0);
            $table->decimal('water_current', 10, 2)->default(0);
            $table->decimal('water_rate', 10, 2)->default(0);
            $table->decimal('water_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['room_id', 'billing_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
