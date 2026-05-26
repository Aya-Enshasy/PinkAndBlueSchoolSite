<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Pink & Blue School') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen login-theme flex items-center justify-center p-4">
    <div class="absolute inset-0 opacity-50 bg-[radial-gradient(circle_at_15%_20%,#fbcfe8,transparent_32%),radial-gradient(circle_at_80%_15%,#bfdbfe,transparent_34%),radial-gradient(circle_at_40%_80%,#e9d5ff,transparent_36%)]"></div>
    <div class="relative w-full max-w-md">
        {{ $slot }}
    </div>
</body>
</html>
