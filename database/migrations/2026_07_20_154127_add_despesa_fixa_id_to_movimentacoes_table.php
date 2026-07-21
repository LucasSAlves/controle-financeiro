<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->foreignId('despesa_fixa_id')
                ->nullable()
                ->after('grupo_fixo_mensal')
                ->constrained('despesa_fixas')
                ->nullOnDelete();

            /*
             * Impede que a mesma despesa fixa gere dois lançamentos
             * com a mesma data.
             */
            $table->unique(
                ['despesa_fixa_id', 'data'],
                'movimentacoes_despesa_fixa_data_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->dropUnique(
                'movimentacoes_despesa_fixa_data_unique'
            );

            $table->dropConstrainedForeignId('despesa_fixa_id');
        });
    }
};
