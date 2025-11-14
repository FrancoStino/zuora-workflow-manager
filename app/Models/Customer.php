<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'base_url',
    ];

    protected $hidden = [
        'client_secret',
    ];

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
