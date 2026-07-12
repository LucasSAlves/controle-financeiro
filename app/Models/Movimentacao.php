<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimentacao extends Model
{
    protected $table = 'movimentacoes';

    protected $fillable = [
        'user_id',
        'tipo',
        'descricao',
        'valor',
        'data',
        'data_pagamento',
        'aviso_vencimento_enviado_em',
        'categoria',
        'forma_pagamento',
        'status',
        'observacao',
        'parcelado',
        'parcela_fixa',
        'fixo_mensal',
        'mes_atual',
        'total_meses',
        'parcela_atual',
        'total_parcelas',
        'grupo_parcelamento',
        'grupo_fixo_mensal',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data' => 'date',
        'data_pagamento' => 'date',
        'aviso_vencimento_enviado_em' => 'date',
        'parcelado' => 'boolean',
        'parcela_fixa' => 'boolean',
        'fixo_mensal' => 'boolean',
        'mes_atual' => 'integer',
        'total_meses' => 'integer',
        'parcela_atual' => 'integer',
        'total_parcelas' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
