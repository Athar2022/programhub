<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'max_programs',
        'max_applicants',
        'features',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_programs' => 'integer',
            'max_applicants' => 'integer',
            'features' => 'array',
        ];
    }

    // Subscriptions using this plan
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
