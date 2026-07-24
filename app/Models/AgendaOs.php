<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaOs extends Model
{
    protected $table = 'agenda_os';

    protected $fillable = [
        'os_tecnico_id',
        'tecnico_id',
        'data',
        'hora_inicio',
        'hora_fim',
        'ordem',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
        ];
    }

    public function osTecnico(): BelongsTo
    {
        return $this->belongsTo(OsTecnico::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
