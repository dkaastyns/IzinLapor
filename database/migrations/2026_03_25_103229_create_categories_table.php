<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Jalankan skema database
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // <-- Tambahkan ini
            $table->string('slug')->unique(); // <-- Tambahkan ini
            $table->timestamps();
        });
    }

    // Balikkan atau hapus skema database
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};