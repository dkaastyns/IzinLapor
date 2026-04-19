<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // NULL = user belum membaca update terbaru dari admin
            // Diisi timestamp = user sudah membaca
            $table->timestamp('notified_at')->nullable()->after('rating_comment');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
