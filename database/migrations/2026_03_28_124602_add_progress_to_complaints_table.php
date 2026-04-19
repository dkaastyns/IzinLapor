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
            $table->integer('progress')->default(0)->after('status');
        });
    }

    // Balikkan atau hapus skema database
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('progress');
        });
    }
};
