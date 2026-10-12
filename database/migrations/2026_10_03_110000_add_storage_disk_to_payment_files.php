<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track which disk each file landed on, so files saved to the local
     * fallback stay readable after Supabase comes back.
     */
    public function up(): void
    {
        Schema::table('payment_channels', function (Blueprint $table) {
            $table->string('qr_disk')->default('local')->after('qr_path');
        });

        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->string('receipt_disk')->default('local')->after('receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('payment_channels', function (Blueprint $table) {
            $table->dropColumn('qr_disk');
        });

        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->dropColumn('receipt_disk');
        });
    }
};
