<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obsoletes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obsoletes');
    }
};