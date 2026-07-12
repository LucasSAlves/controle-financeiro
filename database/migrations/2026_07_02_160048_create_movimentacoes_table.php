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
        Schema::create('movimentacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('tipo', ['entrada', 'despesa']);

            $table->string('descricao');

            $table->decimal('valor', 10, 2);

            $table->date('data');

            $table->string('categoria')->nullable();

            $table->string('forma_pagamento')->nullable();

            $table->enum('status', ['pago', 'pendente', 'recebido'])
                ->default('pendente');

            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
