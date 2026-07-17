<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
