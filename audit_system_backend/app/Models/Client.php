<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'client_type',
        'address',
    ];

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class);
    }
}
