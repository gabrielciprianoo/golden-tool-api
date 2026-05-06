<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('state')->nullable()->default('incompleta')->change();
        });

        DB::statement("UPDATE requests SET state = CASE 
            WHEN state = 'pendiente_aprobacion' THEN 'pendiente_compra'
            ELSE state
        END");

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('state', ['incompleta', 'pendiente_compra', 'entrega_confirmada', 'cancelada'])->default('incompleta')->change();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('state')->nullable()->default('incompleta')->change();
        });

        DB::statement("UPDATE requests SET state = CASE 
            WHEN state = 'pendiente_compra' THEN 'pendiente_aprobacion'
            WHEN state = 'entrega_confirmada' THEN 'pendiente_aprobacion'
            ELSE state
        END");

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('state', ['incompleta', 'pendiente_aprobacion', 'cancelada'])->default('incompleta')->change();
        });
    }
};