<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE requests MODIFY COLUMN state ENUM('pendiente', 'en_proceso', 'finalizado', 'aprobado', 'rechazado') DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE requests MODIFY COLUMN state ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente'");
    }
};