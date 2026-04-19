<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Category;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Pusher\PushNotifications\PushNotifications;

class ComplaintController extends Controller
{
    // User: Dashboard beranda dengan statistik laporan
    public function userDashboard()
    {
        $user = auth()->user();

        // Alihkan admin ke halaman yang sesuai
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        // Optimasi: Menggunakan satu query agregat untuk menghitung statistik
        $stats = Complaint::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
            ")
            ->first();

        $recentComplaints = Complaint::with(['category.parent'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => (int) $stats->total,
                'pending' => (int) $stats->pending,
                'processing' => (int) $stats->processing,
                'resolved' => (int) $stats->resolved,
            ],
            'recentComplaints' => $recentComplaints,
        ]);
    }

    // User: Menampilkan daftar riwayat pengaduan milik sendiri
    public function index(Request $request)
    {
        $query = Complaint::with(['user', 'category.parent'])
            ->where('user_id', auth()->id());

        // Filter status laporan dari sisi server
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Pengurutan data dari sisi server
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'status':
                $query->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 WHEN 'resolved' THEN 2 WHEN 'rejected' THEN 3 END");
                break;
            default: // newest
                $query->latest();
                break;
        }

        // Mengambil jumlah total per status untuk diletakkan di badge angka
        $statusCounts = Complaint::where('user_id', auth()->id())
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        return Inertia::render('Complaints/Index', [
            'complaints' => $query->paginate(6)->withQueryString(),
            'statusCounts' => [
                'total' => (int) $statusCounts->total,
                'pending' => (int) $statusCounts->pending,
                'processing' => (int) $statusCounts->processing,
                'resolved' => (int) $statusCounts->resolved,
                'rejected' => (int) $statusCounts->rejected,
            ],
            'filters' => $request->only(['status', 'sort']),
        ]);
    }

    // User: Menampilkan halaman formulir pengajuan laporan
    public function create()
    {
        // Cache: Kategori disimpan di memori selama 1 jam
        $categories = Cache::remember('categories_with_children', 3600, function () {
            return Category::whereNull('parent_id')
                ->with('children')
                ->get();
        });

        return Inertia::render('Complaints/Create', [
            'categories' => $categories,
        ]);
    }

    // User: Memproses penyimpanan data laporan baru dari formulir
    public function store(Request $request)
    {
        // Login detail validation error ke log Railway untuk debugging
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string|min:20',
                'location' => 'required|string',
                'location_detail' => 'nullable|string|max:500',
                'images' => 'required|array|min:1|max:3',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            ], [
                'images.required' => 'Foto bukti wajib diunggah minimal 1 foto.',
                'images.min' => 'Foto bukti wajib diunggah minimal 1 foto.',
                'images.max' => 'Maksimal 3 foto yang dapat diunggah.',
                'images.*.image' => 'File harus berupa gambar.',
                'images.*.max' => 'Ukuran setiap foto maksimal 1 MB.',
                'images.*.mimes' => 'Format foto harus JPG, JPEG, atau PNG.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed: ' . json_encode($e->errors()));
            throw $e;
        }

        // Menyaring file kosong dan mengunggah ke Cloudinary
        $paths = [];
        $uploadErrors = [];
        $folder = config('cloudinary.folder', 'pengaduan-sman11') . '/complaints';
        $files = $request->file('images') ?? [];
        foreach ($files as $image) {
            if ($image && $image->isValid() && $image->getSize() > 0) {
                // Keamanan: Validasi bahwa file benar-benar gambar
                $imageInfo = @getimagesize($image->getPathname());
                if ($imageInfo === false) {
                    continue; // Melewati file yang bukan gambar murni
                }

                try {
                    // Mengunggah ke Cloudinary via SDK v3
                    $result = Cloudinary::uploadApi()->upload($image->getRealPath(), [
                        'folder' => $folder,
                        'transformation' => [
                            ['quality' => 'auto', 'fetch_format' => 'auto']
                        ],
                    ]);
                    
                    // Cloudinary SDK v3 return array
                    if (isset($result['secure_url'])) {
                        $paths[] = $result['secure_url'];
                    } else {
                        throw new \Exception("URL gagal didapatkan dari Cloudinary");
                    }
                } catch (\Exception $e) {
                    $errMsg = $e->getMessage();
                    \Log::error('Cloudinary Upload Error: ' . $errMsg);
                    $uploadErrors[] = $errMsg;
                }
            }
        }

        // Jika semua foto gagal upload, tampilkan error diagnostik
        if (empty($paths)) {
            $debugMsg = 'Foto gagal diunggah ke Cloudinary.';
            if (!empty($uploadErrors) && config('app.debug') === false) {
                // Tampilkan error singkat agar bisa didiagnosis (hapus setelah fixed)
                $debugMsg .= ' Error: ' . substr(implode(' | ', $uploadErrors), 0, 200);
            }
            return back()->withErrors(['images' => $debugMsg])->withInput();
        }

        Complaint::create([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            // Keamanan: Membersihkan tag HTML (mencegah XSS)
            'title' => strip_tags($request->title),
            'description' => strip_tags($request->description),
            'location' => strip_tags($request->location),
            'location_detail' => $request->location_detail ? strip_tags($request->location_detail) : null,
            'image_path' => $paths[0],   // Kompatibilitas data lama
            'image_paths' => $paths,
            'status' => 'pending',
        ]);

        // Performa: Menghapus cache saat ada data baru
        Cache::forget('admin_dashboard_stats');

        // Mengirimkan notifikasi push ke admin menggunakan Pusher Beams
        try {
            if (config('services.pusher_beams.secret_key')) {
                $beamsClient = new PushNotifications(
                    array(
                        "instanceId" => config('services.pusher_beams.instance_id'),
                        "secretKey" => config('services.pusher_beams.secret_key'),
                    )
                );

                $publishResponse = $beamsClient->publishToInterests(
                    array("admin-notifications"),
                    array(
                        "web" => array(
                            "notification" => array(
                                "title" => "Laporan Baru Masuk!",
                                "body" => "Ada pengaduan baru dari " . auth()->user()->name . ". Segera periksa.",
                                "deep_link" => route('admin.complaints')
                            )
                        )
                    )
                );
            }
        } catch (\Exception $e) {
            \Log::error('Pusher Beams Error (Store): ' . $e->getMessage());
        }

        return redirect()->route('complaints.index')->with('message', 'Laporan berhasil terkirim!');
    }

    // User/Admin: Menampilkan detail spesifik satu pengaduan
    public function show(Complaint $complaint)
    {
        // User hanya bisa lihat complaint miliknya
        if (!auth()->user()->is_admin && $complaint->user_id !== auth()->id()) {
            abort(403);
        }

        $complaint->load(['user', 'category']);

        return Inertia::render('Complaints/Show', [
            'complaint' => $complaint,
        ]);
    }

    // Admin: Menampilkan tabel daftar semua pengaduan yang masuk
    public function adminIndex(Request $request)
    {
        $query = Complaint::with(['user', 'category.parent']);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->category_id) {
            $parentCat = Category::find($request->category_id);
            if ($parentCat) {
                $childIds = $parentCat->children()->pluck('id')->toArray();
                $query->whereIn('category_id', array_merge([$parentCat->id], $childIds));
            }
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Pengurutan data dari sisi server
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'status':
                $query->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 WHEN 'resolved' THEN 2 WHEN 'rejected' THEN 3 END");
                break;
            case 'rating':
                $query->orderByDesc('rating');
                break;
            default: // newest
                $query->latest();
                break;
        }

        return Inertia::render('Admin/Complaints', [
            'complaints' => $query->paginate(6)->withQueryString(),
            'categories' => Cache::remember('categories_parent_only', 3600, function () {
                return Category::whereNull('parent_id')->get();
            }),
            'filters' => $request->only(['status', 'category_id', 'search', 'sort']),
            'overdueCount' => Complaint::whereIn('status', ['pending', 'processing'])
                ->whereNotNull('estimated_completion_date')
                ->whereDate('estimated_completion_date', '<', now()->toDateString())
                ->count(),
        ]);
    }

    // Admin: Menampilkan dashboard utama admin beserta grafiknya
    public function adminDashboard(Request $request)
    {
        // Optimasi: Menggunakan satu query agregat untuk semua statistik
        $stats = Cache::remember('admin_dashboard_stats', 30, function () {
            $raw = Complaint::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")->first();

            $ratingStats = Complaint::where('status', 'resolved')
                ->whereNotNull('rating')
                ->selectRaw('ROUND(AVG(rating)::numeric, 1) as avg_rating, COUNT(*) as rated_count')
                ->first();

            return [
                'total' => (int) $raw->total,
                'pending' => (int) $raw->pending,
                'processing' => (int) $raw->processing,
                'resolved' => (int) $raw->resolved,
                'rejected' => (int) $raw->rejected,
                'avg_rating' => (float) ($ratingStats->avg_rating ?? 0),
                'rated_count' => (int) ($ratingStats->rated_count ?? 0),
            ];
        });

        $query = Complaint::with(['user', 'category.parent']);

        // Filter status laporan dari sisi server untuk tabel
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Pengurutan data dari sisi server
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'status':
                $query->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 WHEN 'resolved' THEN 2 WHEN 'rejected' THEN 3 END");
                break;
            case 'rating':
                $query->orderByDesc('rating');
                break;
            default:
                $query->latest();
                break;
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Optimasi: Menghitung total dalam 1 query untuk menghindari N+1
        $sevenDaysAgo = now()->subDays(7);

        $recentCountsByCategory = Complaint::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id');

        $categories = Cache::remember('categories_dashboard', 300, function () {
            return Category::whereNull('parent_id')
                ->withCount(['complaints as own_complaints_count'])
                ->with([
                    'children' => function ($q) {
                        $q->withCount('complaints');
                    }
                ])
                ->get();
        });

        // Menggabungkan perhitungan tanpa memicu query tambahan (Menghindari masalah N+1)
        $categories = $categories->map(function ($cat) use ($recentCountsByCategory) {
            $childTotal = $cat->children->sum('complaints_count');
            $cat->complaints_count = $cat->own_complaints_count + $childTotal;

            // Perbaikan: Menggunakan hitungan awal daripada query berulang
            $ownRecent = $recentCountsByCategory->get($cat->id, 0);
            $childRecent = $cat->children->sum(function ($child) use ($recentCountsByCategory) {
                return $recentCountsByCategory->get($child->id, 0);
            });
            $cat->recent_complaints_count = $ownRecent + $childRecent;

            return $cat;
        });

        $worstFacilities = Complaint::selectRaw('
                location, 
                COUNT(*) as total,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent_count
            ', [$sevenDaysAgo])
            ->groupBy('location')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentComplaints' => $query->paginate(6)->withQueryString(),
            'categories' => $categories,
            'filters' => $request->only(['search', 'sort', 'status']),
            'worstFacilities' => $worstFacilities,
            'overdueCount' => Complaint::whereIn('status', ['pending', 'processing'])
                ->whereNotNull('estimated_completion_date')
                ->whereDate('estimated_completion_date', '<', now()->toDateString())
                ->count(),
        ]);
    }

    // Admin: Menghapus data laporan secara permanen beserta lampirannya
    public function destroy(Complaint $complaint)
    {
        // Menghapus seluruh foto yang terlampir pada laporan
        $allPaths = $complaint->image_paths ?? ($complaint->image_path ? [$complaint->image_path] : []);
        foreach ($allPaths as $path) {
            try {
                if (str_contains($path, 'cloudinary.com')) {
                    // Mengekstrak public_id dari URL Cloudinary untuk dihapus
                    $publicId = $this->extractCloudinaryPublicId($path);
                    if ($publicId) {
                        Cloudinary::uploadApi()->destroy($publicId);
                    }
                } else {
                    // Fallback: Menghapus dari storage lokal (data lama)
                    Storage::disk('public')->delete($path);
                }
            } catch (\Exception $e) {
                \Log::error('Error menghapus foto: ' . $e->getMessage());
            }
        }

        $complaint->delete();

        // Menghapus cache dashboard admin
        Cache::forget('admin_dashboard_stats');
        Cache::forget('categories_dashboard');

        return redirect()->back()->with('message', 'Laporan berhasil dihapus!');
    }

    // Helper: Mengekstrak public_id dari URL Cloudinary
    private function extractCloudinaryPublicId(string $url): ?string
    {
        // URL format: https://res.cloudinary.com/CLOUD/image/upload/v123/folder/filename.ext
        if (preg_match('/\/upload\/(?:v\d+\/)?(.+?)\.[a-zA-Z]+$/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    // Admin: Memproses perubahan status pengaduan dan menembak notifikasi
    public function updateStatus(Request $request, Complaint $complaint)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,resolved,rejected',
            'admin_response' => 'nullable|string|max:1000',
            'estimated_completion_date' => 'nullable|date',
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $progress = $request->progress;
        if ($request->status === 'resolved' || $request->status === 'rejected') {
            $progress = 100;
        }

        $complaint->update([
            'status' => $request->status,
            // Keamanan: Membersihkan teks balasan admin dari injeksi HTML
            'admin_response' => $request->admin_response ? strip_tags($request->admin_response) : null,
            'estimated_completion_date' => $request->estimated_completion_date,
            'progress' => $progress,
            'notified_at' => null, // Reset nilai ini agar pengguna kembali mendapatkan notifikasi
        ]);

        // Menghapus cache dashboard setelah terjadi perubahan status
        Cache::forget('admin_dashboard_stats');

        // Mengirimkan notifikasi push ke pengguna
        try {
            if (config('services.pusher_beams.secret_key')) {
                $beamsClient = new PushNotifications(
                    array(
                        "instanceId" => config('services.pusher_beams.instance_id'),
                        "secretKey" => config('services.pusher_beams.secret_key'),
                    )
                );

                $statusIndo = match ($request->status) {
                    'processing' => 'Diproses',
                    'resolved' => 'Selesai',
                    'rejected' => 'Ditolak',
                    default => 'Pending'
                };

                $publishResponse = $beamsClient->publishToInterests(
                    array("user-" . $complaint->user_id),
                    array(
                        "web" => array(
                            "notification" => array(
                                "title" => "Status Laporan Diperbarui!",
                                "body" => "Status laporan Anda telah diubah menjadi: " . $statusIndo,
                                "deep_link" => route('complaints.index')
                            )
                        )
                    )
                );
            }
        } catch (\Exception $e) {
            \Log::error('Pusher Beams Error (Update Status): ' . $e->getMessage());
        }

        return redirect()->back()->with('message', 'Status laporan berhasil diperbarui!');
    }

    // User: Menghitung total notifikasi laporan yang belum dibaca
    public function unreadNotificationCount()
    {
        $count = Complaint::where('user_id', auth()->id())
            ->whereNull('notified_at')
            ->whereIn('status', ['processing', 'resolved', 'rejected'])
            ->count();

        return response()->json(['count' => $count]);
    }

    // User: Memperbarui status semua notifikasi menjadi sudah dibaca
    public function markNotificationsRead()
    {
        Complaint::where('user_id', auth()->id())
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]);

        return response()->json(['success' => true]);
    }

    // User: Memperbarui status 1 notifikasi terpilih menjadi sudah dibaca
    public function markSingleNotificationRead(Complaint $complaint)
    {
        // Hanya pemilik yang dapat menandai laporannya sudah dibaca
        if ($complaint->user_id !== auth()->id()) {
            abort(403);
        }

        if ($complaint->notified_at === null && $complaint->status !== 'pending') {
            $complaint->update(['notified_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    // Admin: Menghitung jumlah laporan masuk (pending) yang belum dibuka
    public function adminUnreadNotificationCount()
    {
        // Menghitung laporan pending yang belum disadari oleh admin
        $count = Complaint::whereNull('admin_notified_at')
            ->where('status', 'pending')
            ->count();

        // Mengambil judul laporan terbaru untuk dimunculkan sebagai pop-up notifikasi
        $latest = Complaint::whereNull('admin_notified_at')
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->first();

        return response()->json([
            'count' => $count,
            'latest' => $latest ? [
                'title' => $latest->title,
                'userName' => $latest->user?->name ?? 'Siswa',
                'id' => $latest->id,
            ] : null,
        ]);
    }

    // Admin: Memperbarui status semua notifikasi laporan baru menjadi sudah dibaca
    public function adminMarkNotificationsRead()
    {
        Complaint::whereNull('admin_notified_at')
            ->where('status', 'pending')
            ->update(['admin_notified_at' => now()]);

        return response()->json(['success' => true]);
    }

    // Admin: Memperbarui status 1 notifikasi laporan terpilih menjadi sudah dibaca
    public function adminMarkSingleNotificationRead(Complaint $complaint)
    {
        if ($complaint->admin_notified_at === null && $complaint->status === 'pending') {
            $complaint->update(['admin_notified_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    // User: Memproses pemberian rating dan ulasan pada laporan yang telah selesai
    public function submitRating(Request $request, Complaint $complaint)
    {
        // Hanya pemilik laporan
        if ($complaint->user_id !== auth()->id()) {
            abort(403);
        }

        // Hanya laporan yang sudah resolved
        if ($complaint->status !== 'resolved') {
            return redirect()->back()->withErrors(['rating' => 'Rating hanya bisa diberikan untuk laporan yang sudah selesai.']);
        }

        // Cek sudah pernah dirating
        if ($complaint->rating !== null) {
            return redirect()->back()->withErrors(['rating' => 'Anda sudah memberikan rating untuk laporan ini.']);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'rating_comment' => 'nullable|string|max:500',
        ]);

        $complaint->update([
            'rating' => $request->rating,
            // Keamanan: Membersihkan teks ulasan
            'rating_comment' => $request->rating_comment ? strip_tags($request->rating_comment) : null,
        ]);

        // Menghapus cache statistik karena rata-rata rating telah berubah
        Cache::forget('admin_dashboard_stats');

        return redirect()->back()->with('message', 'Terima kasih atas rating Anda!');
    }
}