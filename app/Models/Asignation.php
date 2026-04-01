<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignation extends Model
{
    protected $fillable = [
        'tool_id',
        'worker_id',
        'assigned_quantity',
        'state',
        'date',
    ];

    // 🔗 Relación con Tool
    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    // 🔗 Relación con Worker
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}