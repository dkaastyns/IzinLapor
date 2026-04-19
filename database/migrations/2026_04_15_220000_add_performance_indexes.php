<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menambahkan indeks database pada kolom yang sering di-query untuk secara drastis
    // meningkatkan performa filter, sorting, dan agregasi data.
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Indeks kolom tunggal untuk kolom yang sering difilter
            $table->index('status');
            $table->index('location');
            $table->index('created_at');
            $table->index('notified_at');
            $table->index('admin_notified_at');
            $table->index('estimated_completion_date');

            // Indeks gabungan untuk pola query yang umum
            // Digunakan oleh: query penghitung overdue (WHERE status IN(...) AND estimated_completion_date < now())
            $table->index(['status', 'estimated_completion_date'], 'idx_complaints_status_est_date');

            // Digunakan oleh: penghitung notifikasi pengguna (WHERE user_id = ? AND notified_at IS NULL AND status IN(...))
            $table->index(['user_id', 'status', 'notified_at'], 'idx_complaints_user_status_notified');

            // Digunakan oleh: penghitung notifikasi admin (WHERE admin_notified_at IS NULL AND status = 'pending')
            $table->index(['admin_notified_at', 'status'], 'idx_complaints_admin_notified_status');
        });

        Schema::table('categories', function (Blueprint $table) {
            // Indeks untuk query relasi parent-child (kategori)
            $table->index('parent_id');
        });
    }

    // Balikkan atau hapus skema database
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['location']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['notified_at']);
            $table->dropIndex(['admin_notified_at']);
            $table->dropIndex(['estimated_completion_date']);
            $table->dropIndex('idx_complaints_status_est_date');
            $table->dropIndex('idx_complaints_user_status_notified');
            $table->dropIndex('idx_complaints_admin_notified_status');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
        });
    }
};
