<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('receber_aviso_email')->default(true);
            $table->boolean('receber_aviso_whatsapp')->default(false);
            $table->string('telefone_whatsapp', 20)->nullable();
            $table->timestamp('whatsapp_consentimento_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'receber_aviso_email',
                'receber_aviso_whatsapp',
                'telefone_whatsapp',
                'whatsapp_consentimento_em',
            ]);
        });
    }
};
