<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_reports', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('period')->index();
            $table->text('missed_reason')->nullable()->after('notes');
            $table->text('admin_note')->nullable()->after('missed_reason');
            $table->timestamp('overridden_at')->nullable()->after('submitted_at');
            $table->foreignId('overridden_by')->nullable()->after('overridden_at')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('checklist_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overridden_by');
            $table->dropColumn(['status', 'missed_reason', 'admin_note', 'overridden_at']);
            $table->timestamp('submitted_at')->nullable(false)->change();
        });
    }
};
