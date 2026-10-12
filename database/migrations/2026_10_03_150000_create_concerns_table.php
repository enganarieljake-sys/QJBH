<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant concerns and reports that are not repair jobs (noise, billing
     * questions, safety, suggestions), with the admin's reply.
     */
    public function up(): void
    {
        Schema::create('concerns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('category')->index();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('open')->index();
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concerns');
    }
};
