<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workflow extends Model
{
    protected $fillable = [
        'customer_id',
        'zuora_id',
        'name',
        'description',
        'state',
        'created_on',
        'updated_on',
        'last_synced_at',
        'json_export',
    ];

    protected $casts = [
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'last_synced_at' => 'datetime',
        'json_export' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
