@php
    $isTeacherLogin = ($loginRole ?? 'admin') === 'teacher';
    $roleTitle = $isTeacherLogin ? 'دخول المعلم' : 'دخول المدير';
    $roleKicker = $isTeacherLogin ? 'قسم المعلمين' : 'قسم الإدارة';
    $heroTitle = $isTeacherLogin ? 'أهلا معلم مدرسة بينك أند بلو' : 'أهلا مدير المدرسة';
    $heroText = $isTeacherLogin
        ? 'أدخل بيانات المعلم للوصول إلى لوحة إدخال الدروس ومتابعة ما يظهر للطالب.'
        : 'أدخل بيانات المدير للوصول إلى لوحة إدارة الطلاب والأنشطة.';
    $emailPlaceholder = $isTeacherLogin ? 'teacher@school.com' : 'admin@school.com';
    $submitText = $isTeacherLogin ? 'دخول لوحة المعلم' : 'دخول لوحة المدير';
    $noteText = $isTeacherLogin
        ? 'بيانات المعلم الافتراضية: teacher@school.com / teacher123'
        : 'بيانات المدير الافتراضية: admin@school.com / admin123';
@endphp

<x-guest-layout>
    <section class="portal-card admin-login-card">
        <a href="{{ route('role.portal') }}" class="portal-back">رجوع لاختيار المستخدم</a>

        <div class="portal-brand">
            <img src="{{ asset('brand-logo.png') }}" alt="مدرسة بينك أند بلو">
            <div>
                <span>بينك أند بلو</span>
                <strong>{{ $roleTitle }}</strong>
            </div>
        </div>

        <div class="portal-hero">
            <span class="portal-kicker">{{ $roleKicker }}</span>
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroText }}</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="portal-form">
            @csrf
            <input type="hidden" name="role" value="{{ $isTeacherLogin ? 'teacher' : 'admin' }}">

            <label>
                <span>البريد الإلكتروني</span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="{{ $emailPlaceholder }}">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </label>

            <label>
                <span>كلمة المرور</span>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="********">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </label>

            <button type="submit" class="portal-submit">{{ $submitText }}</button>
        </form>

        <p class="portal-note">{{ $noteText }}</p>
    </section>
</x-guest-layout>
