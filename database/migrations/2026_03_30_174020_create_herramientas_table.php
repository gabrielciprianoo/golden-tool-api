<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('herramientas', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('categoria');
        $table->decimal('precio', 10, 2);
        $table->string('proveedor');
        $table->date('fecha_ingreso');
        $table->integer('cantidad');
        $table->integer('cantidad_no_asignada');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('herramientas');
    }
};
