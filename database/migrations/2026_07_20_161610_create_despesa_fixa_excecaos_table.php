<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesa_fixa_excecoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('despesa_fixa_id')
                ->constrained('despesa_fixas')
                ->cascadeOnDelete();

            /*
             * Primeiro dia do mês que não deverá ser gerado.
             * Exemplo: 2026-08-01 representa agosto de 2026.
             */
            $table->date('competencia');

            $table->timestamps();

            $table->unique(
                ['despesa_fixa_id', 'competencia'],
                'despesa_fixa_excecao_competencia_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesa_fixa_excecoes');
    }
};
