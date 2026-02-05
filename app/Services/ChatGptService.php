<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatGptService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    // Лимиты токенов для разных моделей (null = без лимита)
    protected array $modelMaxTokens = [
        'gpt-5' => null,   // Reasoning модель - без лимита
        'gpt-4o' => 16384,
        'gpt-4o-mini' => 16384,
        'gpt-4-turbo' => 4096,
        'gpt-4' => 4096,
        'gpt-3.5-turbo' => 4096,
    ];

    // Модели, которые используют max_completion_tokens вместо max_tokens
    protected array $useCompletionTokensParam = [
        'gpt-5',
        'gpt-4o',
        'gpt-4o-mini',
    ];

    public function __construct()
    {
        $this->apiKey = Setting::getChatGptApiKey() ?? '';
        $this->model = Setting::getChatGptModel();
    }

    /**
     * Получить максимальное количество токенов для текущей модели
     */
    protected function getMaxTokens(): ?int
    {
        return $this->modelMaxTokens[$this->model] ?? 4096;
    }

    /**
     * Генерация кода сайта по промпту
     */
    public function generateSiteCode(string $promptText): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'API ключ ChatGPT не настроен',
                'files' => [],
            ];
        }

        $systemPrompt = $this->getSystemPrompt();

        Log::info('ChatGPT request starting', ['model' => $this->model]);

        try {
            $requestBody = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $promptText,
                    ],
                ],
            ];

            // Новые модели используют max_completion_tokens и не поддерживают temperature
            $maxTokens = $this->getMaxTokens();

            if (in_array($this->model, $this->useCompletionTokensParam)) {
                // Добавляем лимит только если он задан
                if ($maxTokens !== null) {
                    $requestBody['max_completion_tokens'] = $maxTokens;
                }
                // gpt-5 не поддерживает temperature, остальные поддерживают
                if ($this->model !== 'gpt-5') {
                    $requestBody['temperature'] = 0.7;
                }
            } else {
                if ($maxTokens !== null) {
                    $requestBody['max_tokens'] = $maxTokens;
                }
                $requestBody['temperature'] = 0.7;
            }

            $response = Http::timeout(900)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, $requestBody);

            if (!$response->successful()) {
                Log::error('ChatGPT API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Ошибка API: ' . $response->status(),
                    'files' => [],
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            Log::info('ChatGPT response received', [
                'tokens_used' => $data['usage'] ?? [],
                'content_length' => strlen($content),
            ]);

            // Парсим ответ в файлы
            $files = $this->parseResponseToFiles($content);

            if (empty($files)) {
                return [
                    'success' => false,
                    'message' => 'Не удалось разобрать ответ на файлы',
                    'files' => [],
                    'raw_response' => $content,
                ];
            }

            return [
                'success' => true,
                'message' => 'Код сайта успешно сгенерирован',
                'files' => $files,
                'tokens_used' => $data['usage'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Исключение: ' . $e->getMessage(),
                'files' => [],
            ];
        }
    }

    /**
     * Системный промпт для ChatGPT
     */
    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a professional web developer. Generate complete website code based on user requirements.

IMPORTANT: Output format must be EXACTLY as follows for each file:

--- FILE: filename.php ---
[file content here]

--- FILE: css/style.css ---
[file content here]

--- FILE: js/script.js ---
[file content here]

Rules:
1. Always start with index.php as the main file
2. Use this exact format: --- FILE: path/filename.ext ---
3. Create all necessary files (PHP, CSS, JS, images placeholders)
4. Include proper HTML5 structure
5. Make responsive design with CSS
6. Do not include any explanation text, only file contents
7. Each file must start with --- FILE: and end before the next --- FILE:
8. Use relative paths for all links and includes
PROMPT;
    }

    /**
     * Парсинг ответа ChatGPT в массив файлов
     */
    protected function parseResponseToFiles(string $content): array
    {
        $files = [];

        // Паттерн для поиска файлов: --- FILE: filename ---
        $pattern = '/---\s*FILE:\s*([^\s-]+(?:\/[^\s-]+)*)\s*---/i';

        // Разбиваем контент по маркерам файлов
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        // parts[0] = текст до первого файла (игнорируем)
        // parts[1] = имя первого файла
        // parts[2] = содержимое первого файла
        // parts[3] = имя второго файла
        // parts[4] = содержимое второго файла
        // и т.д.

        for ($i = 1; $i < count($parts) - 1; $i += 2) {
            $filename = trim($parts[$i]);
            $fileContent = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';

            if (!empty($filename) && !empty($fileContent)) {
                // Убираем возможные markdown блоки кода
                $fileContent = $this->cleanFileContent($fileContent);
                $files[$filename] = $fileContent;
            }
        }

        return $files;
    }

    /**
     * Очистка содержимого файла от markdown разметки
     */
    protected function cleanFileContent(string $content): string
    {
        // Убираем ```php, ```html, ```css и т.д. в начале
        $content = preg_replace('/^```\w*\s*/m', '', $content);

        // Убираем ``` в конце
        $content = preg_replace('/\s*```$/m', '', $content);

        return trim($content);
    }

    /**
     * Сохранение файлов проекта в storage
     */
    public function saveProjectFiles(int $projectId, array $files): array
    {
        $storagePath = "sites/project_{$projectId}";
        $totalSize = 0;

        foreach ($files as $filename => $content) {
            $filePath = $storagePath . '/' . $filename;

            // Создаём директорию если нужно
            $directory = dirname($filePath);
            if (!Storage::disk('local')->exists($directory)) {
                Storage::disk('local')->makeDirectory($directory);
            }

            // Сохраняем файл
            Storage::disk('local')->put($filePath, $content);
            $totalSize += strlen($content);
        }

        return [
            'storage_path' => $storagePath,
            'files_count' => count($files),
            'total_size' => $totalSize,
        ];
    }

    /**
     * Проверка доступности API
     */
    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'API ключ не настроен',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Соединение успешно',
                ];
            }

            return [
                'success' => false,
                'message' => 'Ошибка API: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Исключение: ' . $e->getMessage(),
            ];
        }
    }
}
