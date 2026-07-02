<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpTaskAnexo extends Model
{
    public $timestamps = false;

    protected $table = 'op_task_anexos';

    protected $fillable = [
        'op_task_id',
        'nome_arquivo',
        'mime_type',
        'tamanho_bytes',
        'conteudo_base64',
        'enviado_por',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'op_task_id' => 'integer',
            'tamanho_bytes' => 'integer',
            'criado_em' => 'datetime',
        ];
    }

    public function opTask(): BelongsTo
    {
        return $this->belongsTo(OpTask::class, 'op_task_id');
    }
}
