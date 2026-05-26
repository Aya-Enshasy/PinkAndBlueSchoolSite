<?php

return [
    'enabled' => (bool) env('STUDENT_SHEET_SYNC_ENABLED', false),
    'webhook_url' => env('STUDENT_SHEET_SYNC_WEBHOOK_URL', ''),
    'token' => env('STUDENT_SHEET_SYNC_TOKEN', ''),
    'timeout_seconds' => (int) env('STUDENT_SHEET_SYNC_TIMEOUT', 10),
];

