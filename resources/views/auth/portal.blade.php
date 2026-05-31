<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pink &amp; Blue School</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="role-select-page">
    <main class="role-select-shell">
        <section class="role-select-card">
            <header class="role-select-brand">
                <img src="{{ asset('brand-logo.png') }}" alt="مدرسة بينك أند بلو">
            </header>

            <h1>حدد نوع المستخدم</h1>

            <div class="role-select-grid">
                <a href="{{ route('student.platform') }}" class="role-select-option">
                    <span class="role-select-art">
                        <img src="{{ asset('assets/roles/student-reader.jpg') }}" alt="طالب يقرأ كتاب">
                    </span>
                    <span class="role-select-copy">
                        <strong>طلاب</strong>
                        <small>الدروس والتمارين والإنجازات اليومية</small>
                    </span>
                </a>

                <a href="{{ route('teacher.platform') }}" class="role-select-option">
                    <span class="role-select-art">
                        <img src="{{ asset('assets/roles/teacher-role-v2.png') }}" alt="معلمة تشرح أمام اللوح">
                    </span>
                    <span class="role-select-copy">
                        <strong>معلم</strong>
                        <small>دخول آمن لإدارة الوحدات والدروس</small>
                    </span>
                </a>

                <a href="{{ route('dashboard') }}" class="role-select-option">
                    <span class="role-select-art">
                        <img src="{{ asset('assets/roles/manager-role.jpg') }}" alt="مديرة تستخدم الكمبيوتر">
                    </span>
                    <span class="role-select-copy">
                        <strong>مدير</strong>
                        <small>دخول آمن للوحة الإدارة والطلاب</small>
                    </span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
