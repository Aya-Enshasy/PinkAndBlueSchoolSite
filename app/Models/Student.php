<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function sessions(): HasMany
    {
        return $this->hasMany(StudentSession::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function latestProgress(): HasOne
    {
        return $this->hasOne(StudentProgress::class)->latestOfMany();
    }

    public function gradeNumber(): int
    {
        $index = array_search($this->grade, self::GRADE_OPTIONS, true);

        return $index === false ? 1 : $index + 1;
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
