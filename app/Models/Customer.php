<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
