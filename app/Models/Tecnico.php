<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tecnico extends Model
{
    protected $table = 'tecnicos';

    protected $fillable = [
        'nome',
        'username',
        'regiao',
        'google_chat_id',
        'nicon_user_id',
        'nicon_mention_name',
    ];

    public function agenda(): HasMany
    {
        return $this->hasMany(AgendaOs::class);
    }

    public function indisponibilidades(): HasMany
    {
        return $this->hasMany(TecnicoIndisponibilidade::class);
    }
}
