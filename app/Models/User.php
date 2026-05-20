<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id';

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
            'updated_at' => 'datetime',
        ];
    }
}