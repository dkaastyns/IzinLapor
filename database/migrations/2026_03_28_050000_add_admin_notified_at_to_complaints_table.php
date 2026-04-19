<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // NULL = admin hasn't seen/acknowledged this complaint yet
            $table->timestamp('admin_notified_at')->nullable()->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('admin_notified_at');
        });
    }
};
