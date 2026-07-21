<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespesaFixaExcecao extends Model
{
    protected $table = 'despesa_fixa_excecoes';

    protected $fillable = [
        'despesa_fixa_id',
        'competencia',
    ];

    protected $casts = [
        'competencia' => 'date',
    ];

    public function despesaFixa(): BelongsTo
    {
        return $this->belongsTo(DespesaFixa::class);
    }
}
