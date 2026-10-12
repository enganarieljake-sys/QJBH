<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Old receipt images are deleted to save storage; the payment record
     * stays, and this marks that its image is gone.
     */
    public function up(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->timestamp('receipt_purged_at')->nullable()->after('receipt_disk');
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->dropColumn('receipt_purged_at');
        });
    }
};
