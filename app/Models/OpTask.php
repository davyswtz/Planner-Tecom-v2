<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpTask extends Model
{
    const CREATED_AT = 'criadaEm';
    const UPDATED_AT = 'updated_at';


    protected $table = "op_tasks";
    protected $primaryKey = "id";
    protected $fillable = [
        'taskCode',
        'titulo',
        'setor',
        'regiao',
        'responsavel',
        'clientesAfetados',
        'coordenadas',
        'localizacao_texto',
        'descricao',
        'categoria',
        'prazo',
        'prioridade',
        'status',
        'is_parent_task',
        'parent_task_id',
        'historico',
        'active_duration_minutes',
        'chat_thread_key',
        'nome_cliente',
        'protocolo',
        'data_entrada',
        'data_instalacao',
        'assinada_por',
        'assinada_em',
        'cto'
        
    ];
}
