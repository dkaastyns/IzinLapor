<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Cleanup users with non-compliant email domains.
     *
     * Allowed domains:
     * - Admin (is_admin = true): @sman11.sch.id
     * - User  (is_admin = false): @student.sman11.sch.id
     */
    public function up(): void
    {
        // Menghapus pengguna non-admin yang emailnya tidak berakhiran @student.sman11.sch.id
        $deletedUsers = DB::table('users')
            ->where('is_admin', false)
            ->where('email', 'NOT LIKE', '%@student.sman11.sch.id')
            ->delete();

        if ($deletedUsers > 0) {
            Log::info("Cleanup migration: Removed {$deletedUsers} non-compliant user account(s).");
        }

        // Menghapus pengguna admin yang emailnya tidak berakhiran @sman11.sch.id
        $deletedAdmins = DB::table('users')
            ->where('is_admin', true)
            ->where('email', 'NOT LIKE', '%@sman11.sch.id')
            ->delete();

        if ($deletedAdmins > 0) {
            Log::info("Cleanup migration: Removed {$deletedAdmins} non-compliant admin account(s).");
        }
    }

    // Migrasi ini tidak dapat dibalik (data yang dihapus tidak dapat dipulihkan).
    public function down(): void
    {
        // Tidak dapat mengembalikan pengguna yang dihapus
    }
};
