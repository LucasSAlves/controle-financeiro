<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DespesaFixa extends Model
{
    protected $table = 'despesa_fixas';

    protected $fillable = [
        'user_id',
        'grupo_recorrencia',
        'descricao',
        'valor',
        'data_inicio',
        'dia_vencimento',
        'categoria',
        'forma_pagamento',
        'observacao',
        'ativa',
        'encerrada_em',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_inicio' => 'date',
        'dia_vencimento' => 'integer',
        'ativa' => 'boolean',
        'encerrada_em' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (DespesaFixa $despesaFixa) {
            if (!$despesaFixa->grupo_recorrencia) {
                $despesaFixa->grupo_recorrencia =
                    (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }
}
