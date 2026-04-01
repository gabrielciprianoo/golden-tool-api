<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asignations', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('tool_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');

            // Campos propios
            $table->enum('state', [
                'nuevo',
                'buen estado',
                'regular',
                'mal estado',
                'obsoleto'
            ]);

            $table->date('date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignations');
    }
};