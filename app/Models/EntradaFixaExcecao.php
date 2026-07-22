<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntradaFixaExcecao extends Model
{
    protected $table = 'entrada_fixa_excecoes';

    protected $fillable = [
        'entrada_fixa_id',
        'competencia',
    ];

    protected $casts = [
        'competencia' => 'date',
    ];

    public function entradaFixa(): BelongsTo
    {
        return $this->belongsTo(EntradaFixa::class);
    }
}
