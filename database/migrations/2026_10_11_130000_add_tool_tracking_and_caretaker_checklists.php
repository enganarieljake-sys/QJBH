<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('tools_taken_photo')->nullable();
            $table->string('tools_taken_disk')->nullable();
            $table->text('tools_used')->nullable();
            $table->timestamp('tools_taken_at')->nullable();
            $table->string('tools_returned_photo')->nullable();
            $table->string('tools_returned_disk')->nullable();
            $table->timestamp('tools_returned_at')->nullable();
            $table->timestamp('tools_verified_at')->nullable();
            $table->foreignId('tools_verified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('period')->default('both');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->string('period');
            $table->json('completed_items');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['user_id', 'report_date', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_reports');
        Schema::dropIfExists('checklist_items');

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tools_verified_by');
            $table->dropColumn([
                'tools_taken_photo', 'tools_taken_disk', 'tools_used', 'tools_taken_at',
                'tools_returned_photo', 'tools_returned_disk', 'tools_returned_at', 'tools_verified_at',
            ]);
        });
    }
};
