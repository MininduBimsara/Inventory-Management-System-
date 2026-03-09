<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location',
        'description',
    ];

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
