<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioPermissao extends Model
{
    protected $table = 'usuario_permissoes';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'permissao',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }
}
