<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional contract end date, shown to tenants in their invitation.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->date('lease_end_date')->nullable()->after('move_in_date');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('lease_end_date');
        });
    }
};
