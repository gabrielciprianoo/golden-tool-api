<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'price',
        'supplier',
        'entry_date',
        'quantity',
        'unassigned_quantity',
        'warranty',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function asignations()
    {
        return $this->hasMany(Asignation::class);
    }
}
