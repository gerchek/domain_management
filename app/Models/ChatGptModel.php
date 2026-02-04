<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatGptModel extends Model
{
    protected $table = 'chatgpt_models';

    protected $fillable = [
        'name',
        'model_id',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function getActiveModels()
    {
        return self::active()->ordered()->get();
    }

    public static function getDefaultModelId(): string
    {
        $model = self::active()->ordered()->first();
        return $model ? $model->model_id : 'gpt-4';
    }
}
