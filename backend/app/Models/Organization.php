<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo',
        'website',
        'address',
        'status',
    ];

    
     // Organization members and their roles
 
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    // Programs managed by the organization

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    // Organization subscription history

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
