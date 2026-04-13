<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LostTool extends Model
{
    protected $fillable = [
        'review_id',
        'tool_id',
        'worker_id',
        'quantity_lost',
        'lost_at',
    ];

    protected $casts = [
        'lost_at' => 'date',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
