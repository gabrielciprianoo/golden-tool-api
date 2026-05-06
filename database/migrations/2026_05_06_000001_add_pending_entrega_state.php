<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('state', [
                'incompleta', 
                'pendiente_compra', 
                'pendiente_entrega', 
                'entrega_confirmada', 
                'cancelada'
            ])->default('incompleta')->change();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('state', ['incompleta', 'pendiente_compra', 'entrega_confirmada', 'cancelada'])->default('incompleta')->change();
        });
    }
};