<?php

namespace Database\Seeders;

use App\Models\ChatGptModel;
use Illuminate\Database\Seeder;

class ChatGptModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'name' => 'GPT-4',
                'model_id' => 'gpt-4',
                'description' => 'Best quality',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'GPT-4 Turbo',
                'model_id' => 'gpt-4-turbo',
                'description' => 'Faster, cheaper',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'GPT-4o',
                'model_id' => 'gpt-4o',
                'description' => 'Latest multimodal',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'GPT-4o Mini',
                'model_id' => 'gpt-4o-mini',
                'description' => 'Fast and affordable',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'GPT-3.5 Turbo',
                'model_id' => 'gpt-3.5-turbo',
                'description' => 'Cheapest option',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($models as $model) {
            ChatGptModel::firstOrCreate(
                ['model_id' => $model['model_id']],
                $model
            );
        }
    }
}
