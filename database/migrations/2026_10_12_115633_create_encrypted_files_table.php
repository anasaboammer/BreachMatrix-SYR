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
        Schema::create('encrypted_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('victim_id')->constrained('victims')->cascadeOnDelete();
            $table->foreignId('payload_id')->constrained('payloads')->cascadeOnDelete();
            $table->string('file_path'); 
            $table->string('status')->default('encrypted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encrypted_files');
    }
};
