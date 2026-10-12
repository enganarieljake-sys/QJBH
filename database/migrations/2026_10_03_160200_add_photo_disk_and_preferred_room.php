<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Room photos move to the payment files disk (older photos stay on the
     * public disk), and applicants can pick the room they want.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('photo_disk')->default('public')->after('photo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_room_id')->nullable()->after('role')->constrained('rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_room_id');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('photo_disk');
        });
    }
};
