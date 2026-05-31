<x-app-layout>
    <x-slot name="header">تفاصيل الطالب</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-3">
            <h3 class="text-2xl font-bold">{{ $student->full_name }}</h3>
            <p><span class="font-semibold">الصف:</span> {{ $student->grade }}</p>
            <p><span class="font-semibold">السنة الدراسية:</span> {{ $student->academic_year }}</p>
            <p><span class="font-semibold">رقم هوية الطالب:</span> {{ $student->student_id_number }}</p>
            <p><span class="font-semibold">رقم هوية الأب:</span> {{ $student->father_id_number }}</p>
            <p><span class="font-semibold">رقم الجوال:</span> {{ $student->mobile_number }}</p>
            <p><span class="font-semibold">رقم جوال بديل:</span> {{ $student->alternative_mobile_number ?: '-' }}</p>
            <p><span class="font-semibold">تاريخ الميلاد:</span> {{ $student->birth_date->format('Y-m-d') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4">
                <div class="rounded-2xl bg-sky-50 border border-sky-100 p-4">
                    <p class="text-sm text-slate-500">نقاط المنصة</p>
                    <strong class="text-2xl text-sky-700">{{ (int) ($student->learning_xp ?? 0) }} XP</strong>
                </div>
                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                    <p class="text-sm text-slate-500">دروس مكتملة</p>
                    <strong class="text-2xl text-emerald-700">{{ (int) ($student->completed_lessons_count ?? 0) }}</strong>
                </div>
                <div class="rounded-2xl bg-pink-50 border border-pink-100 p-4">
                    <p class="text-sm text-slate-500">دروس بدأها</p>
                    <strong class="text-2xl text-pink-700">{{ (int) ($student->started_lessons_count ?? 0) }}</strong>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <a href="{{ route('students.edit', $student) }}" class="rounded-xl bg-amber-500 text-white px-4 py-2">تعديل</a>
                <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('هل أنت متأكد من حذف الطالب؟');">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl bg-rose-600 text-white px-4 py-2">حذف</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-4">
            <h4 class="font-bold mb-3">المرفقات</h4>
            <div class="grid grid-cols-1 gap-3 text-xs">
                @foreach(['student_id_image' => 'هوية الطالب','father_id_image' => 'هوية الأب','birth_certificate_image' => 'شهادة الميلاد'] as $field => $label)
                    @if(!empty($student->{$field}))
                        <a href="{{ $student->imageUrl($field) }}" target="_blank" class="block">
                            <img src="{{ $student->imageUrl($field) }}" alt="{{ $label }}" class="w-full h-32 object-cover rounded-2xl shadow-sm">
                            <p class="mt-1 text-center">{{ $label }}</p>
                        </a>
                    @else
                        <div class="rounded-2xl border border-slate-200 p-3 text-center text-slate-500">
                            {{ $label }}: غير مرفقة
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h4 class="font-bold text-xl">ملخص تعلم الطالب</h4>
                <p class="text-sm text-slate-500 mt-1">يبين أين وصل الطالب داخل منصة التعلم.</p>
            </div>
        </div>

        @if($progressRows->isEmpty())
            <div class="rounded-2xl bg-slate-50 p-6 text-center text-slate-500">
                لم يدخل الطالب بهويته بعد، لذلك لا يوجد تقدم محفوظ.
            </div>
        @else
            <div class="grid gap-3">
                @foreach($progressRows as $progress)
                    <article class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                            <div>
                                <h5 class="font-semibold text-slate-900">{{ $progress->lesson_title }}</h5>
                                <p class="text-sm text-slate-500">
                                    الصف {{ $progress->grade }} · {{ $progress->subject }} · الوحدة {{ $progress->unit_no ?: '-' }}
                                </p>
                            </div>
                            <div class="flex gap-2 text-sm">
                                <span class="rounded-full bg-sky-50 text-sky-700 px-3 py-1">{{ $progress->xp }} XP</span>
                                <span class="rounded-full {{ $progress->completed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1">
                                    {{ $progress->completed ? 'مكتمل' : 'قيد التعلم' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <span class="block h-full bg-sky-500" style="width: {{ $progress->progress_percent }}%"></span>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">آخر تحديث: {{ $progress->updated_at?->format('Y-m-d H:i') }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
