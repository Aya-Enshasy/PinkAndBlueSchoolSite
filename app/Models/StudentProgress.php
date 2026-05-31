<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lesson_key',
        'lesson_title',
        'grade',
        'subject',
        'unit_no',
        'xp',
        'streak',
        'hearts',
        'progress_percent',
        'current_block',
        'completed',
        'sections',
        'activity',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'unit_no' => 'integer',
            'xp' => 'integer',
            'streak' => 'integer',
            'hearts' => 'integer',
            'progress_percent' => 'integer',
            'current_block' => 'integer',
            'completed' => 'boolean',
            'sections' => 'array',
            'activity' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
