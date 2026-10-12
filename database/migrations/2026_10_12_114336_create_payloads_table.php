<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payloads', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        
            $table->string('target_os');               
            $table->string('encryption_type');        
            $table->string('icon_path')->nullable();   
            $table->json('target_directories')->nullable();
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payloads');
    }
};
