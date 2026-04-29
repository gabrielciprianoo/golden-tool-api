<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->foreignId('tool_id')->constrained()->onDelete('cascade');

            // Campos propios
            $table->string('type_request');
            $table->string('signa_applicant')->nullable();
            $table->string('signa_authorization')->nullable();
            $table->text('details_tool')->nullable();
            $table->string('preferred_brand')->nullable();
            $table->enum('state', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
