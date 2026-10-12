<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Changes QJ Bot proposed. Nothing runs until an admin presses Confirm,
     * and every row doubles as the activity log of what the bot did.
     */
    public function up(): void
    {
        Schema::create('assistant_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 60);
            $table->string('title');
            $table->json('lines');
            $table->json('plan');
            $table->string('status', 20)->default('pending');
            $table->text('result_message')->nullable();
            $table->json('created_records')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('undo_until')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_actions');
    }
};
