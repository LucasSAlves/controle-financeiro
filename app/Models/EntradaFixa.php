<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EntradaFixa extends Model
{
    protected $table = 'entrada_fixas';

    protected $fillable = [
        'user_id',
        'grupo_recorrencia',
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

    protected static function booted(): void
    {
        static::creating(function (EntradaFixa $entradaFixa) {
            if (!$entradaFixa->grupo_recorrencia) {
                $entradaFixa->grupo_recorrencia =
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
