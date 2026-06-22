<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    protected $table = 'app_config';

    protected $primaryKey = 'cfg_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['cfg_key', 'cfg_value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);

        if (! $row || $row->cfg_value === null) {
            return $default;
        }

        return $row->cfg_value;
    }

    public static function getJson(string $key, array $default = []): array
    {
        $raw = static::getValue($key);

        if (! is_string($raw) || $raw === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $default;
    }

    public static function setJson(string $key, array $value): void
    {
        static::updateOrCreate(
            ['cfg_key' => $key],
            ['cfg_value' => json_encode($value, JSON_UNESCAPED_UNICODE)]
        );
    }
}
