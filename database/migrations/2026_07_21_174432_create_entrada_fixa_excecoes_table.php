<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrada_fixa_excecoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrada_fixa_id')
                ->constrained('entrada_fixas')
                ->cascadeOnDelete();

            $table->date('competencia');

            $table->timestamps();

            /*
             * Impede que a mesma competência seja ignorada
             * mais de uma vez para a mesma entrada fixa.
             */
            $table->unique([
                'entrada_fixa_id',
                'competencia',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrada_fixa_excecoes');
    }
};
