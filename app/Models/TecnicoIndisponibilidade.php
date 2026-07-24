<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TecnicoIndisponibilidade extends Model
{
    protected $table = 'tecnico_indisponibilidades';

    protected $fillable = [
        'tecnico_id',
        'motivo',
        'data_inicio',
        'data_fim',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
