<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuario';

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'pass_salt',
        'pass_hash',
        'pass_iterations',
    ];

    protected $hidden = [
        'pass_salt',
        'pass_hash',
        'pass_iterations',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'pass_iterations' => 'integer',
        ];
    }

    public function permissoes(): HasMany
    {
        return $this->hasMany(UsuarioPermissao::class, 'username', 'username');
    }
}
