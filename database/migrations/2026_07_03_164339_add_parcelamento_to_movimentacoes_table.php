<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->boolean('parcelado')->default(false)->after('status');
            $table->unsignedInteger('parcela_atual')->nullable()->after('parcelado');
            $table->unsignedInteger('total_parcelas')->nullable()->after('parcela_atual');
            $table->string('grupo_parcelamento')->nullable()->after('total_parcelas');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->dropColumn([
                'parcelado',
                'parcela_atual',
                'total_parcelas',
                'grupo_parcelamento',
            ]);
        });
    }
};
