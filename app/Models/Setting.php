<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget("setting.{$key}");
    }

    public static function getDynadotApiKey(): ?string
    {
        return self::get('dynadot_api_key');
    }

    public static function getDomainsPerRequest(): int
    {
        return (int) self::get('domains_per_request', 20);
    }

    public static function getChatGptApiKey(): ?string
    {
        return self::get('chatgpt_api_key');
    }

    public static function getChatGptModel(): string
    {
        return self::get('chatgpt_model', 'gpt-4');
    }
}
