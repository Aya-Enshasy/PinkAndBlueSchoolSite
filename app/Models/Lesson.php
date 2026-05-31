<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade',
        'subject',
        'unit_no',
        'unit_title',
        'title',
        'status',
        'xp_reward',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'unit_no' => 'integer',
            'xp_reward' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(LessonBlock::class)->orderBy('sort_order');
    }
}
