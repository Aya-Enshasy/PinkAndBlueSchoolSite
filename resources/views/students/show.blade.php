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
</x-app-layout>
