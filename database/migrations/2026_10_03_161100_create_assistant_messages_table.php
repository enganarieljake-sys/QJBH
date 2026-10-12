<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat history between the admin and QJ Bot. ai_requests counts the
     * OpenRouter calls an answer used, to stay inside the free daily limit.
     */
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('content');
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('ai_requests')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
