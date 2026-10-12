<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('electricity_rate', 10, 2)->default(12)->after('monthly_rate');
            $table->decimal('water_rate', 10, 2)->default(60)->after('electricity_rate');
        });

        DB::table('rooms')->update([
            'electricity_rate' => Setting::number('electricity_rate'),
            'water_rate' => Setting::number('water_rate'),
        ]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['electricity_rate', 'water_rate']);
        });
    }
};
