<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('billing_month');
            $table->decimal('room_rate', 10, 2);
            $table->decimal('electricity_amount', 10, 2)->default(0);
            $table->decimal('water_amount', 10, 2)->default(0);
            $table->decimal('other_charges', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->date('due_date');
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'billing_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
