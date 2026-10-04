<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reference_points', function (Blueprint $table) {
            $table->string('description')->nullable()->after('label');
        });

        Schema::table('gps_observations', function (Blueprint $table) {
            $table->string('device_label')->nullable()->after('captured_by');
            $table->string('conditions')->nullable()->after('device_label');
        });
    }

    public function down(): void
    {
        Schema::table('reference_points', fn (Blueprint $t) => $t->dropColumn('description'));
        Schema::table('gps_observations', fn (Blueprint $t) => $t->dropColumn(['device_label', 'conditions']));
    }
};
