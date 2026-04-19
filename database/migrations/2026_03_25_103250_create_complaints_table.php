<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Jalankan skema database
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            // Data utama
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->string('image_path')->nullable();

            // Status
            $table->enum('status', ['pending', 'processing', 'resolved', 'rejected'])
                  ->default('pending');

            $table->timestamps();
        });
    }

    // Balikkan atau hapus skema database
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};