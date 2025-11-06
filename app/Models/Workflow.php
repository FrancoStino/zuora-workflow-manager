<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $fillable = [
        'id',
        'name',
        'description',
        'state',
        'created_on',
        'updated_on',
    ];

    protected $casts = [
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
    ];
}
