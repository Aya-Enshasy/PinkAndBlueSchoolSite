<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>متابعة الطلاب | Pink &amp; Blue School</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="teacher-progress-page">
    <main class="teacher-progress-shell">
        <header class="teacher-progress-header">
            <div>
                <span>لوحة المعلم</span>
                <h1>متابعة تقدم الطلاب</h1>
                <p>عرض فقط: فلترة حسب الصف والمادة ومتابعة أين وصل كل طالب.</p>
            </div>
            <div class="teacher-progress-actions">
                <a href="{{ route('teacher.platform') }}">لوحة بناء الدروس</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">تسجيل خروج</button>
                </form>
            </div>
        </header>

        <section class="teacher-summary-grid">
            <article>
                <span>طلاب نشطون</span>
                <strong>{{ $summary['activeStudents'] }}</strong>
            </article>
            <article>
                <span>دروس مكتملة</span>
                <strong>{{ $summary['completedLessons'] }}</strong>
            </article>
            <article>
                <span>نقاط مكتسبة</span>
                <strong>{{ $summary['totalXp'] }}</strong>
            </article>
        </section>

        <form method="GET" class="teacher-progress-filters">
            <input type="text" name="search" value="{{ $search }}" placeholder="بحث باسم الطالب أو رقم الهوية">
            <select name="grade">
                <option value="">كل الصفوف</option>
                @foreach($grades as $gradeOption)
                    <option value="{{ $gradeOption }}" @selected($grade === $gradeOption)>{{ $gradeOption }}</option>
                @endforeach
            </select>
            <select name="subject">
                <option value="">كل المواد</option>
                <option value="arabic" @selected($subject === 'arabic')>عربي</option>
                <option value="english" @selected($subject === 'english')>إنجليزي</option>
                <option value="math" @selected($subject === 'math')>حساب</option>
                <option value="science" @selected($subject === 'science')>علوم</option>
            </select>
            <button>تصفية</button>
        </form>

        <section class="teacher-progress-list">
            @forelse($students as $student)
                @php($latest = $student->latestProgress)
                <article class="teacher-student-card">
                    <div>
                        <h2>{{ $student->full_name }}</h2>
                        <p>{{ $student->grade }} · {{ $student->student_id_number }}</p>
                    </div>
                    <div class="student-progress-metrics">
                        <span><b>{{ (int) ($student->learning_xp ?? 0) }}</b> XP</span>
                        <span><b>{{ (int) ($student->completed_lessons_count ?? 0) }}</b> مكتمل</span>
                        <span><b>{{ (int) ($student->started_lessons_count ?? 0) }}</b> بدأ</span>
                    </div>
                    <div class="student-progress-latest">
                        @if($latest)
                            <strong>{{ $latest->lesson_title }}</strong>
                            <small>{{ $latest->subject }} · {{ $latest->progress_percent }}%</small>
                            <div class="teacher-progress-bar"><span style="width: {{ $latest->progress_percent }}%"></span></div>
                        @else
                            <strong>لم يبدأ التعلم بعد</strong>
                            <small>سيظهر تقدمه هنا بعد دخوله بهويته.</small>
                            <div class="teacher-progress-bar"><span style="width: 0%"></span></div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="teacher-empty-state">لا توجد نتائج حسب الفلاتر الحالية.</div>
            @endforelse
        </section>

        <div class="teacher-pagination">{{ $students->links() }}</div>
    </main>
</body>
</html>
