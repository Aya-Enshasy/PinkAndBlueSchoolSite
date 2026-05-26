<x-guest-layout>
    <section class="portal-card admin-login-card">
        <a href="{{ route('role.portal') }}" class="portal-back">رجوع لاختيار المستخدم</a>

        <div class="portal-brand">
            <img src="{{ asset('brand-logo.png') }}" alt="Pink & Blue School">
            <div>
                <span>Pink & Blue</span>
                <strong>دخول المدير</strong>
            </div>
        </div>

        <div class="portal-hero">
            <span class="portal-kicker">قسم الإدارة</span>
            <h1>أهلًا مدير المدرسة</h1>
            <p>أدخل بيانات المدير للوصول إلى لوحة إدارة الطلاب والأنشطة.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="portal-form">
            @csrf

            <label>
                <span>البريد الإلكتروني</span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@school.com">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </label>

            <label>
                <span>كلمة المرور</span>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </label>

            <button type="submit" class="portal-submit">دخول لوحة المدير</button>
        </form>

        <p class="portal-note">بيانات المدير الافتراضية: admin@school.com / admin123</p>
    </section>
</x-guest-layout>
