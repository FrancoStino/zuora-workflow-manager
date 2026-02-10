<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable
        = [
            'workflow_id',
            'task_id',
            'name',
            'description',
            'state',
            'action_type',
            'object',
            'object_id',
            'call_type',
            'next_task_id',
            'priority',
            'concurrent_limit',
            'parameters',
            'css',
            'tags',
            'assignment',
            'zuora_org_id',
            'zuora_org_ids',
            'subprocess_id',
            'created_on',
            'updated_on',
        ];

    protected $casts
        = [
            'created_on' => 'datetime',
            'updated_on' => 'datetime',
            'parameters' => 'array',
            'css' => 'array',
            'tags' => 'array',
            'assignment' => 'array',
            'zuora_org_ids' => 'array',
        ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
