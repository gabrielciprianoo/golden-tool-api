<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Herramienta extends Model
{
    protected $fillable = [
    'nombre',
    'categoria',
    'precio',
    'proveedor',
    'fecha_ingreso',
    'cantidad',
    'cantidad_no_asignada'
];
}
