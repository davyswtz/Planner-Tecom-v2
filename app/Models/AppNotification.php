<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notification';

    protected $fillable = [
        'kind',
        'title',
        'message',
        'ref_type',
        'ref_id',
        'op_category',
        'created_by',
        'username',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'ref_id' => 'integer',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
