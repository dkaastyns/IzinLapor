<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Jalankan skema database
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->tinyInteger('rating')->nullable()->after('admin_response');
            $table->text('rating_comment')->nullable()->after('rating');
        });
    }

    // Balikkan atau hapus skema database
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_comment']);
        });
    }
};
