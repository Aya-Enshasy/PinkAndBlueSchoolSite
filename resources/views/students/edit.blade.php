<x-app-layout>
    <x-slot name="header">تعديل بيانات الطالب</x-slot>

    <form method="POST"
          action="{{ route('students.update', $student) }}"
          enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow border border-slate-100 p-6 space-y-4">

        @csrf
        @method('POST')

        @include('students._form')

        <button class="rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-2">
            حفظ التعديلات
        </button>
    </form>
</x-app-layout>
