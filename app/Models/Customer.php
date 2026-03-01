<?php

namespace App\Models;

use App\Casts\EncryptedCastZuoraClientSecret;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'zuora_client_id',
        'zuora_client_secret',
        'zuora_base_url',
    ];

    protected $hidden = [
        'zuora_client_secret',
    ];

    protected $casts = [
        'zuora_client_secret' => EncryptedCastZuoraClientSecret::class,
    ];

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
