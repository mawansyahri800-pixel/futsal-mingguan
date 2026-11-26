<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_date',
        'day',
        'start_time',
        'end_time',
        'location',
        'rental_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'game_date' => 'date',
            'rental_cost' => 'integer',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
