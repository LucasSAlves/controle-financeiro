<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->foreignId('entrada_fixa_id')
                ->nullable()
                ->after('despesa_fixa_id')
                ->constrained('entrada_fixas')
                ->nullOnDelete();

            $table->index([
                'user_id',
                'entrada_fixa_id',
                'data',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->dropIndex([
                'user_id',
                'entrada_fixa_id',
                'data',
            ]);

            $table->dropConstrainedForeignId('entrada_fixa_id');
        });
    }
};
