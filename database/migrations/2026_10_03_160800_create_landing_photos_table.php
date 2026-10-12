<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photos Ate Jackie shows on the public landing page gallery.
     */
    public function up(): void
    {
        Schema::create('landing_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_photos');
    }
};
