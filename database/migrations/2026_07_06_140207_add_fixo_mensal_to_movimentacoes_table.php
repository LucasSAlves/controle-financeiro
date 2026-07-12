<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->boolean('fixo_mensal')
                ->default(false)
                ->after('parcela_fixa');

            $table->unsignedInteger('mes_atual')
                ->nullable()
                ->after('fixo_mensal');

            $table->unsignedInteger('total_meses')
                ->nullable()
                ->after('mes_atual');

            $table->string('grupo_fixo_mensal')
                ->nullable()
                ->after('grupo_parcelamento');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->dropColumn([
                'fixo_mensal',
                'mes_atual',
                'total_meses',
                'grupo_fixo_mensal',
            ]);
        });
    }
};
