<x-app-layout>
    <x-slot name="header">إضافة طالب جديد</x-slot>
    <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow border border-slate-100 p-6 space-y-4">
        @include('students._form')
        <button class="rounded-xl bg-gradient-to-r from-sky-600 to-indigo-700 text-white px-6 py-2">حفظ الطالب</button>
    </form>
</x-app-layout>

