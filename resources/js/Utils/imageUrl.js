// Helper: Menentukan URL gambar secara otomatis
// Mendukung URL Cloudinary (full) dan path lokal (/storage/)
export function resolveImageUrl(path) {
    if (!path) return '';
    // Jika sudah berupa full URL (Cloudinary atau CDN lain)
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    // Jika path relatif lokal (data lama)
    return '/storage/' + path;
}

// Helper: Mendapatkan array URL gambar dari objek complaint
export function resolveComplaintImages(complaint) {
    if (!complaint) return [];
    if (complaint.image_paths?.length > 0) {
        return complaint.image_paths.map(resolveImageUrl);
    }
    if (complaint.image_path) {
        return [resolveImageUrl(complaint.image_path)];
    }
    return [];
}
