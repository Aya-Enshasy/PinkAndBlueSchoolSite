<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Pink & Blue School') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="saas-page text-slate-800">
    @include('partials.flash-alerts')

    <div class="min-h-screen p-3 sm:p-5">
        <div class="mx-auto max-w-[1540px] rounded-[2rem] border border-white/70 bg-white/70 shadow-[0_18px_80px_rgba(15,23,42,0.12)] backdrop-blur-xl overflow-hidden animate-fade-in">
            <div class="flex min-h-[calc(100vh-2.5rem)]">
                <aside class="hidden md:flex w-72 bg-white/88 border-l border-slate-100 flex-col p-5">
                    <div class="flex items-center gap-3 mb-7">
                        <img src="{{ asset('brand-logo.png') }}" alt="Pink & Blue" class="h-14 w-14 rounded-xl object-contain bg-white p-1 border border-slate-200">
                        <div>
                            <p class="font-semibold text-slate-900">Pink & Blue</p>
                            <p class="text-xs text-slate-500">إدارة المدرسة</p>
                        </div>
                    </div>

                    <p class="menu-label">القائمة الرئيسية</p>
                    <nav class="space-y-2 mb-6">
                        <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'menu-item-active' : '' }}">
                            <span>📊</span><span>لوحة التحكم</span>
                        </a>
                        <a href="{{ route('students.index') }}" class="menu-item {{ request()->routeIs('students.*') ? 'menu-item-active' : '' }}">
                            <span>🎓</span><span>إدارة الطلاب</span>
                        </a>
                    </nav>

                    <p class="menu-label">إجراءات سريعة</p>
                    <a href="{{ route('students.create') }}" class="menu-item mb-2"><span>➕</span><span>إضافة طالب جديد</span></a>
                    <a href="{{ route('dashboard') }}" class="menu-item"><span>🗓️</span><span>التقويم والأنشطة</span></a>

                    <div class="mt-auto">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full rounded-2xl bg-slate-900 text-white py-2.5 text-sm hover:opacity-90 transition">تسجيل خروج</button>
                        </form>
                    </div>
                </aside>

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <header class="saas-header mb-5">
                        <div>
                            <h2 class="text-xl sm:text-3xl font-semibold text-slate-900">{{ $header ?? 'لوحة التحكم' }}</h2>
                            <p class="text-slate-500 text-sm mt-1">إدارة يومك الدراسي بشكل مرن وواضح</p>
                        </div>

                        <div class="flex items-center gap-2 sm:gap-3">
                            <a href="{{ route('dashboard') }}" class="md:hidden rounded-xl bg-slate-100 px-3 py-2 text-sm">الرئيسية</a>
                            <a href="{{ route('students.index') }}" class="md:hidden rounded-xl bg-slate-100 px-3 py-2 text-sm">الطلاب</a>
                            <a href="{{ route('students.create') }}" class="rounded-2xl cute-gradient text-white px-4 sm:px-5 py-2.5 text-sm font-semibold shadow-sm hover:opacity-90 transition">إضافة طالب</a>
                        </div>
                    </header>

                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</body>
</html>
