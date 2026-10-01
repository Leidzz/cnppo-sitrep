<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gps_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_point_id')->constrained();
            $table->foreignId('captured_by')->constrained('users');
            $table->decimal('captured_latitude', 10, 7);
            $table->decimal('captured_longitude', 10, 7);
            $table->decimal('device_accuracy_meters', 8, 2)->nullable();
            $table->decimal('computed_error_meters', 8, 2)->nullable();
            $table->timestamp('device_timestamp');
            $table->timestamp('server_received_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_observations');
    }
};