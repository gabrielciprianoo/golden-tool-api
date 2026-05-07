<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'worker_id',
        'created_by',
        'tool_id',
        'type_request',
        'details_tool',
        'preferred_brand',
        'signa_applicant',
        'signa_authorization',
        'state',
    ];

    public function tool()
    {
        return $this->belongsTo(\App\Models\Tool::class);
    }

    public function worker()
    {
        return $this->belongsTo(\App\Models\Worker::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
