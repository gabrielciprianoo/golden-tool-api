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
            WHEN state = 'finalizado' THEN 'pendiente_aprobacion'
            WHEN state = 'en_proceso' THEN 'incompleta'
            WHEN state = 'pendiente' THEN 'incompleta'
            ELSE 'incompleta'
        END");

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('state', ['incompleta', 'pendiente_aprobacion', 'cancelada'])->default('incompleta')->change();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('state')->nullable()->default('pendiente')->change();
        });

        DB::statement("UPDATE requests SET state = CASE 
            WHEN state = 'incompleta' THEN 'pendiente'
            WHEN state = 'pendiente_aprobacion' THEN 'finalizado'
            WHEN state = 'cancelada' THEN 'rechazado'
            ELSE 'pendiente'
        END");

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('state', ['pendiente', 'en_proceso', 'finalizado', 'aprobado', 'rechazado'])->default('pendiente')->change();
        });
    }
};
