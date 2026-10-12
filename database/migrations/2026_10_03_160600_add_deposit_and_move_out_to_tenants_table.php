<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Security deposit held for each tenant and how it was settled on move-out.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('lease_end_date');
            $table->string('deposit_status')->default('none')->after('deposit_amount');
            $table->date('move_out_date')->nullable()->after('deposit_status');
            $table->decimal('deposit_deductions', 10, 2)->default(0)->after('move_out_date');
            $table->decimal('deposit_refund', 10, 2)->nullable()->after('deposit_deductions');
            $table->text('move_out_notes')->nullable()->after('deposit_refund');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'deposit_status', 'move_out_date', 'deposit_deductions', 'deposit_refund', 'move_out_notes']);
        });
    }
};
