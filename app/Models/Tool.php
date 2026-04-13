<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
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
        public function asignations()
    {
        return $this->hasMany(Asignation::class);
    }
}
