<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember which reminders went out so the daily job never sends twice.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->timestamp('due_reminder_sent_at')->nullable()->after('status');
            $table->timestamp('overdue_notice_sent_at')->nullable()->after('due_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['due_reminder_sent_at', 'overdue_notice_sent_at']);
        });
    }
};
