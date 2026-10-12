<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time codes emailed to an admin to confirm dangerous actions,
     * such as wiping all data.
     */
    public function up(): void
    {
        Schema::create('security_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_codes');
    }
};
