@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-semibold mb-1">الاسم الرباعي</label>
        <input type="text" name="full_name" value="{{ old('full_name', $student->full_name ?? '') }}" class="w-full rounded-2xl border-slate-200 focus:border-fuchsia-300 focus:ring-fuchsia-200" required>
        @error('full_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">الصف</label>
        <select name="grade" class="w-full rounded-2xl border-slate-200" required>
            <option value="">اختر الصف</option>
            @foreach($grades as $gradeOption)
                <option value="{{ $gradeOption }}" @selected(old('grade', $student->grade ?? '') === $gradeOption)>{{ $gradeOption }}</option>
            @endforeach
        </select>
        @error('grade') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">السنة الدراسية</label>
        <input type="text" name="academic_year" value="{{ old('academic_year', $student->academic_year ?? $defaultAcademicYear) }}" class="w-full rounded-2xl border-slate-200" placeholder="2026/2027" required>
        @error('academic_year') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">رقم هوية الطالب</label>
        <input type="text" name="student_id_number" value="{{ old('student_id_number', $student->student_id_number ?? '') }}" class="w-full rounded-2xl border-slate-200" required>
        @error('student_id_number') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">رقم هوية الأب</label>
        <input type="text" name="father_id_number" value="{{ old('father_id_number', $student->father_id_number ?? '') }}" class="w-full rounded-2xl border-slate-200" required>
        @error('father_id_number') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">رقم الجوال</label>
        <input type="text" name="mobile_number" value="{{ old('mobile_number', $student->mobile_number ?? '') }}" class="w-full rounded-2xl border-slate-200" required>
        @error('mobile_number') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">رقم جوال بديل</label>
        <input type="text" name="alternative_mobile_number" value="{{ old('alternative_mobile_number', $student->alternative_mobile_number ?? '') }}" class="w-full rounded-2xl border-slate-200">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">تاريخ الميلاد</label>
        <input type="date" name="birth_date" value="{{ old('birth_date', isset($student) ? $student->birth_date->format('Y-m-d') : '') }}" class="w-full rounded-2xl border-slate-200" required>
        @error('birth_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    @foreach(['student_id_image' => 'صورة هوية الطالب','father_id_image' => 'صورة هوية الأب','birth_certificate_image' => 'صورة شهادة الميلاد'] as $field => $label)
        <div>
            <label class="block text-sm font-semibold mb-1">{{ $label }}</label>
            <input
                type="file"
                name="{{ $field }}"
                accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                class="w-full rounded-2xl border-slate-200"
            >
            @isset($student)
                @if(!empty($student->{$field}))
                    <img src="{{ $student->imageUrl($field) }}" class="mt-2 w-24 h-24 object-cover rounded-xl shadow" alt="{{ $label }}">
                @else
                    <p class="mt-2 text-xs text-slate-500">لا يوجد ملف مرفق.</p>
                @endif
            @endisset
            @error($field) <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    @endforeach
</div>
