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
            $table->text('admin_response')->nullable()->after('status');
        });
    }

    // Balikkan atau hapus skema database
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('admin_response');
        });
    }
};
