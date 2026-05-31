<x-app-layout>
    <x-slot name="header">قائمة الطلاب</x-slot>

    <div class="bg-white rounded-2xl shadow border border-slate-100 p-4 mb-4">
        <form class="grid grid-cols-1 md:grid-cols-4 gap-3" method="GET">
            <input type="text" name="search" value="{{ $search }}" placeholder="ابحث بالاسم أو هوية الطالب أو هوية الأب" class="rounded-xl border-slate-300 md:col-span-2">

            <select name="grade" class="rounded-xl border-slate-300">
                <option value="">كل الصفوف</option>
                @foreach($grades as $gradeOption)
                    <option value="{{ $gradeOption }}" @selected($grade === $gradeOption)>{{ $gradeOption }}</option>
                @endforeach
            </select>

            <select name="academic_year" class="rounded-xl border-slate-300">
                <option value="">كل السنوات</option>
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" @selected($academicYear === $year)>{{ $year }}</option>
                @endforeach
            </select>

            <button class="rounded-xl bg-sky-600 text-white px-5 py-2 md:col-span-4">بحث وتصفية</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        @if($students->isEmpty())
            <div class="p-10 text-center text-slate-500">لا توجد بيانات طلاب حاليًا.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3 text-right">الاسم</th>
                        <th class="p-3 text-right">الصف</th>
                        <th class="p-3 text-right">السنة الدراسية</th>
                        <th class="p-3 text-right">هوية الطالب</th>
                        <th class="p-3 text-right">الجوال</th>
                        <th class="p-3 text-right">تقدم المنصة</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($students as $student)
                        <tr class="hover:bg-slate-50 {{ ($highlightId ?? 0) === $student->id ? 'bg-emerald-50 ring-1 ring-emerald-200' : '' }}">
                            <td class="p-3">{{ $student->full_name }}</td>
                            <td class="p-3">{{ $student->grade }}</td>
                            <td class="p-3">{{ $student->academic_year }}</td>
                            <td class="p-3">{{ $student->student_id_number }}</td>
                            <td class="p-3">{{ $student->mobile_number }}</td>
                            <td class="p-3">
                                <div class="min-w-36">
                                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                                        <span>{{ (int) ($student->learning_xp ?? 0) }} XP</span>
                                        <span>{{ (int) ($student->completed_lessons_count ?? 0) }} مكتمل</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <span class="block h-full bg-sky-500" style="width: {{ $student->latestProgress?->progress_percent ?? 0 }}%"></span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ $student->latestProgress?->lesson_title ?? 'لا يوجد نشاط بعد' }}
                                    </p>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 justify-end">
                                    <a href="{{ route('students.show', $student) }}" class="text-sky-700">عرض</a>
                                    <a href="{{ route('students.edit', $student) }}" class="text-amber-600">تعديل</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $students->links() }}</div>
        @endif
    </div>
</x-app-layout>
