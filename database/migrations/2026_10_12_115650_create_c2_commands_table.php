<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('c2_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('victim_id')->constrained('victims')->cascadeOnDelete();
            $table->string('command_type');
            $table->text('payload_data')->nullable();
            $table->string('status')->default('pending');
            $table->text('response_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('c2_commands');
    }
};
