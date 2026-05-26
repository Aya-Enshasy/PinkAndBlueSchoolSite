<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlannerItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'scheduled_for',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
        ];
    }
}
