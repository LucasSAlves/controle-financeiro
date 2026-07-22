<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrada_fixas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('descricao');
            $table->decimal('valor', 12, 2);

            $table->date('data_inicio');
            $table->unsignedTinyInteger('dia_recebimento');

            $table->string('categoria')->nullable();
            $table->text('observacao')->nullable();

            $table->boolean('ativa')->default(true);
            $table->date('encerrada_em')->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'ativa',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrada_fixas');
    }
};
