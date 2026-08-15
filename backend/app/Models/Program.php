<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'type',
        'description',
        'location',
        'delivery_mode',
        'application_start',
        'application_deadline',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'application_start' => 'datetime',
            'application_deadline' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
        ];
    }



    // The organization that manages the program
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // Applications submitted to the program
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
