<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'group',
        'name',
        'payload',
        'locked',
    ];

    protected $casts = [
        'locked' => 'boolean',
    ];
}
