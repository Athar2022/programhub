<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'education',
        'major',
        'graduation_year',
        'experience_years',
        'skills',
        'languages',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'experience_years' => 'integer',
            'skills' => 'array',
            'languages' => 'array',
        ];
    }

    // The user associated with the applicant profile
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
