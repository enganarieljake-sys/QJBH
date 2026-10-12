<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A repair forwarded to a maintenance worker (panday) as a ticket, with
     * the owner's instructions and an optional target date.
     */
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            $table->text('assignment_notes')->nullable()->after('assigned_at');
            $table->date('target_date')->nullable()->after('assignment_notes');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['assigned_at', 'assignment_notes', 'target_date']);
        });
    }
};
