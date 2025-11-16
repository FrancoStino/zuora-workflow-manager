<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'zuora_client_id',
        'zuora_client_secret',
        'zuora_base_url',
    ];

    protected $hidden = [
        'zuora_client_secret',
    ];

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
