<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntradaFixa extends Model
{
    protected $table = 'entrada_fixas';

    protected $fillable = [
        'user_id',
        'descricao',
        'valor',
        'data_inicio',
        'dia_recebimento',
        'categoria',
        'observacao',
        'ativa',
        'encerrada_em',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_inicio' => 'date',
        'dia_recebimento' => 'integer',
        'ativa' => 'boolean',
        'encerrada_em' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }
}
