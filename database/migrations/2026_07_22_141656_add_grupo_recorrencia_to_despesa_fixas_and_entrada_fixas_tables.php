<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despesa_fixas', function (Blueprint $table) {
            $table->uuid('grupo_recorrencia')
                ->nullable()
                ->after('user_id');

            $table->index(
                ['user_id', 'grupo_recorrencia'],
                'despesa_fixas_user_grupo_idx'
            );
        });

        Schema::table('entrada_fixas', function (Blueprint $table) {
            $table->uuid('grupo_recorrencia')
                ->nullable()
                ->after('user_id');

            $table->index(
                ['user_id', 'grupo_recorrencia'],
                'entrada_fixas_user_grupo_idx'
            );
        });

        /*
         * Cada regra antiga recebe inicialmente um identificador próprio.
         *
         * As regras duplicadas do teste serão corrigidas depois,
         * de maneira controlada, sem tentar agrupá-las apenas pela descrição.
         */
        DB::table('despesa_fixas')
            ->whereNull('grupo_recorrencia')
            ->orderBy('id')
            ->chunkById(100, function ($regras) {
                foreach ($regras as $regra) {
                    DB::table('despesa_fixas')
                        ->where('id', $regra->id)
                        ->update([
                            'grupo_recorrencia' => (string) Str::uuid(),
                        ]);
                }
            });

        DB::table('entrada_fixas')
            ->whereNull('grupo_recorrencia')
            ->orderBy('id')
            ->chunkById(100, function ($regras) {
                foreach ($regras as $regra) {
                    DB::table('entrada_fixas')
                        ->where('id', $regra->id)
                        ->update([
                            'grupo_recorrencia' => (string) Str::uuid(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('despesa_fixas', function (Blueprint $table) {
            $table->dropIndex('despesa_fixas_user_grupo_idx');
            $table->dropColumn('grupo_recorrencia');
        });

        Schema::table('entrada_fixas', function (Blueprint $table) {
            $table->dropIndex('entrada_fixas_user_grupo_idx');
            $table->dropColumn('grupo_recorrencia');
        });
    }
};
