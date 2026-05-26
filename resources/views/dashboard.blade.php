<x-app-layout>
    <x-slot name="header">لوحة التحكم</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <section class="lg:col-span-2 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="kpi-card kpi-mint floating-card">
                    <p class="kpi-label">إجمالي الطلاب</p>
                    <h3 class="kpi-value">{{ $totalStudents }}</h3>
                </div>
                <div class="kpi-card kpi-pink floating-card" style="animation-delay:.08s">
                    <p class="kpi-label">طلاب مضافون حديثًا</p>
                    <h3 class="kpi-value">{{ $recentStudents->count() }}</h3>
                </div>
                <div class="kpi-card kpi-lilac floating-card" style="animation-delay:.14s">
                    <p class="kpi-label">إجمالي الملفات</p>
                    <h3 class="kpi-value">{{ $totalFiles }}</h3>
                </div>
            </div>

            <div class="saas-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold">آخر الطلاب</h3>
                    <a href="{{ route('students.index') }}" class="text-sm rounded-xl bg-slate-100 px-3 py-1.5">عرض الكل</a>
                </div>

                @if($recentStudents->isEmpty())
                    <div class="rounded-2xl bg-slate-50 p-8 text-center text-slate-500">لا يوجد طلاب حتى الآن.</div>
                @else
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach($recentStudents as $student)
                            <a href="{{ route('students.show', $student) }}" class="student-mini-card">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $student->full_name }}</p>
                                    <p class="text-sm text-slate-500 mt-1">{{ $student->student_id_number }}</p>
                                </div>
                                <span class="text-lg">↗</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <aside class="xl:col-span-1 xl:max-w-sm xl:justify-self-end space-y-5">
            <div class="saas-panel p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold">تقويم المهام</h3>
                    <span class="text-sm text-slate-500">{{ $monthStart->translatedFormat('F Y') }}</span>
                </div>

                <div class="grid grid-cols-7 gap-2 text-center text-xs mb-2 text-slate-400">
                    @foreach(['أحد','اثن','ثلا','أرب','خمي','جمع','سبت'] as $d)
                        <div>{{ $d }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-2 text-center text-sm">
                    @for($i = 0; $i < $monthStart->dayOfWeek; $i++)
                        <div></div>
                    @endfor

                    @for($day = 1; $day <= $monthEnd->day; $day++)
                        @php
                            $date = $monthStart->copy()->day($day)->format('Y-m-d');
                            $isSelected = $selectedDate->format('Y-m-d') === $date;
                            $hasItems = isset($itemsByDate[$date]);
                        @endphp
                        <a href="{{ route('dashboard', ['date' => $date]) }}"
                           class="day-chip {{ $isSelected ? 'day-chip-active' : '' }} {{ $hasItems ? 'day-chip-has-items' : '' }}">
                            {{ $day }}
                        </a>
                    @endfor
                </div>
            </div>

            <div class="saas-panel p-5 space-y-4">
                <h3 class="text-xl font-semibold">أنشطة يوم {{ $selectedDate->format('Y-m-d') }}</h3>

                <form method="POST" action="{{ route('planner-items.store') }}" class="space-y-2 rounded-2xl bg-slate-50 p-3 border border-slate-100">
                    @csrf
                    <input type="hidden" name="scheduled_for" value="{{ $selectedDate->format('Y-m-d') }}">
                    <input name="title" class="w-full rounded-xl border-slate-200" placeholder="اكتب نشاط جديد..." required>
                    <div class="flex gap-2">
                        <select name="type" class="flex-1 rounded-xl border-slate-200">
                            <option value="task">مهمة</option>
                            <option value="meeting">اجتماع</option>
                            <option value="deadline">موعد نهائي</option>
                        </select>
                        <button class="rounded-xl cute-gradient text-white px-4">إضافة</button>
                    </div>
                </form>

                @forelse($selectedItems as $item)
                    <form method="POST" action="{{ route('planner-items.update', $item) }}" class="activity-edit-card">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="scheduled_for" value="{{ $item->scheduled_for->format('Y-m-d') }}">
                        <input name="title" value="{{ $item->title }}" class="w-full rounded-xl border-slate-200 text-sm" required>
                        <div class="flex gap-2 mt-2">
                            <select name="type" class="flex-1 rounded-xl border-slate-200 text-sm">
                                <option value="task" @selected($item->type === 'task')>مهمة</option>
                                <option value="meeting" @selected($item->type === 'meeting')>اجتماع</option>
                                <option value="deadline" @selected($item->type === 'deadline')>موعد نهائي</option>
                            </select>
                            <button class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs">حفظ</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('planner-items.destroy', $item) }}" class="-mt-2">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs text-rose-600 hover:underline">حذف النشاط</button>
                    </form>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">لا توجد أنشطة لهذا اليوم.</div>
                @endforelse
            </div>
        </aside>
    </div>
</x-app-layout>
