<?php

return [
    'fallback_image_url' => env('CLOUDINARY_FALLBACK_IMAGE_URL'),
    'max_bytes' => (int) env('CLOUDINARY_MAX_UPLOAD_BYTES', 2 * 1024 * 1024),
    'delivery_transformation' => env('CLOUDINARY_DELIVERY_TRANSFORMATION', 'f_auto,q_auto,c_limit,w_1600'),
    'folders' => [
        'students' => env('CLOUDINARY_STUDENTS_FOLDER', 'uploads/students'),
        'teachers' => env('CLOUDINARY_TEACHERS_FOLDER', 'uploads/teachers'),
        'events' => env('CLOUDINARY_EVENTS_FOLDER', 'uploads/events'),
        'general' => env('CLOUDINARY_GENERAL_FOLDER', 'uploads/general'),
    ],
];
