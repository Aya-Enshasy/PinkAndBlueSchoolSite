<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbfdff">
    <title>لوحة المعلم | Pink &amp; Blue School</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/teacher-builder.js'])
</head>
<body class="teacher-builder-body">
    <div class="teacher-top-links">
        <a href="{{ route('teacher.progress') }}">متابعة الطلاب</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">تسجيل خروج</button>
        </form>
    </div>
    <livewire:lesson-builder />
    @livewireScripts
</body>
</html>
