<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    // The organization that owns the subscription
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // The plan assigned to the subscription
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
