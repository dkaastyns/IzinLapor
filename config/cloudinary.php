<?php

return [

    // Cloudinary URL — format: cloudinary://API_KEY:API_SECRET@CLOUD_NAME
    // Pastikan env var ini di-set di Railway dengan format yang benar.
    'cloud_url' => env('CLOUDINARY_URL', ''),

    // Upload preset dari Cloudinary Dashboard (opsional)
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', ''),

    // Folder default untuk menyimpan file di Cloudinary
    'folder' => env('CLOUDINARY_FOLDER', 'pengaduan-sman11'),

    // Webhook URL notifikasi Cloudinary (opsional)
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL', ''),

    // Required by cloudinary-laravel v3 package (jangan dihapus)
    'upload_route'  => env('CLOUDINARY_UPLOAD_ROUTE', null),
    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION', null),

];
