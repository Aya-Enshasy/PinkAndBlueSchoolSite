<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'حقل :attribute يجب أن يكون نصًا.',
    'max' => [
        'string' => 'حقل :attribute يجب ألا يزيد عن :max أحرف.',
        'file' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
    ],
    'min' => [
        'string' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
    ],
    'unique' => ':attribute مستخدم مسبقًا.',
    'image' => 'حقل :attribute يجب أن يكون صورة.',
    'mimes' => 'صيغة :attribute غير مدعومة.',
    'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
    'before_or_equal' => 'حقل :attribute يجب أن يكون تاريخًا في الماضي أو اليوم.',
    'in' => 'القيمة المختارة في :attribute غير صحيحة.',

    'attributes' => [
        'full_name' => 'الاسم الرباعي',
        'grade' => 'الصف',
        'academic_year' => 'السنة الدراسية',
        'student_id_number' => 'رقم هوية الطالب',
        'father_id_number' => 'رقم هوية الأب',
        'mobile_number' => 'رقم الجوال',
        'alternative_mobile_number' => 'رقم الجوال البديل',
        'birth_date' => 'تاريخ الميلاد',
        'student_id_image' => 'صورة هوية الطالب',
        'father_id_image' => 'صورة هوية الأب',
        'birth_certificate_image' => 'صورة شهادة الميلاد',
        'title' => 'عنوان النشاط',
        'scheduled_for' => 'تاريخ النشاط',
        'type' => 'نوع النشاط',
    ],
];
