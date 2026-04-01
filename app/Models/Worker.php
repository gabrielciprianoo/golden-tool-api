<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'lastname', 'area', 'worker_code', 'created_by'])]
class Worker extends Model
{
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔥 RELACIÓN NUEVA
    public function asignations()
    {
        return $this->hasMany(Asignation::class);
    }

    public static function generateWorkerCode(): string
    {
        $prefix = 'TRB-';
        $latest = self::orderBy('id', 'desc')->first();
        $nextNumber = $latest ? (intval(substr($latest->worker_code, 4)) + 1) : 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}