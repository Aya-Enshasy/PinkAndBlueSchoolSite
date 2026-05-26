<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    public const GRADE_OPTIONS = [
        'الصف الأول',
        'الصف الثاني',
        'الصف الثالث',
        'الصف الرابع',
        'الصف الخامس',
        'الصف السادس',
        'الصف السابع',
        'الصف الثامن',
        'الصف التاسع',
        'الصف العاشر',
    ];

    protected $fillable = [
        'full_name',
        'grade',
        'academic_year',
        'student_id_number',
        'father_id_number',
        'mobile_number',
        'alternative_mobile_number',
        'birth_date',
        'student_id_image',
        'father_id_image',
        'birth_certificate_image',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function imageUrl(string $field): ?string
    {
        $value = (string) ($this->{$field} ?? '');

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return asset('storage/'.$value);
    }
}
