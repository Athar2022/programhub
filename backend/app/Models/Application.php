<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'applicant_id',
        'status',
        'submitted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    // The program associated with the application
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    // The user who submitted the application
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    // Documents attached to the application
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
