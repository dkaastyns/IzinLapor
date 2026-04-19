<?php

return [

    // Cloudinary URL (format: cloudinary://API_KEY:API_SECRET@CLOUD_NAME)
    'cloud_url' => env('CLOUDINARY_URL', ''),

    // Upload preset (opsional)
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', ''),

    // Folder untuk menyimpan file di Cloudinary
    'folder' => env('CLOUDINARY_FOLDER', 'pengaduan-sman11'),

    // Notifikasi URL (opsional)
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL', ''),

];
