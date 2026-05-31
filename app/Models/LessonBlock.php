<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'type',
        'subject',
        'category',
        'subtype',
        'title',
        'content',
        'media',
        'settings',
        'is_graded',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'media' => 'array',
            'settings' => 'array',
            'is_graded' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
