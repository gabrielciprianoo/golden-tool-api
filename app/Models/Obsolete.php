<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obsolete extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'worker_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}