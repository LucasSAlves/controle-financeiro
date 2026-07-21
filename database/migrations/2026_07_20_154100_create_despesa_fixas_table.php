<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesa_fixas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('descricao');
            $table->decimal('valor', 15, 2);

            $table->date('data_inicio');
            $table->unsignedTinyInteger('dia_vencimento');

            $table->string('categoria')->nullable();
            $table->string('forma_pagamento')->nullable();
            $table->text('observacao')->nullable();

            $table->boolean('ativa')->default(true);

            /*
             * Primeiro mês que não deverá mais ser gerado.
             * Exemplo: 2026-10-01 encerra a recorrência a partir de outubro.
             */
            $table->date('encerrada_em')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'ativa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesa_fixas');
    }
};
