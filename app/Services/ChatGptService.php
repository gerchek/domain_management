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

    // API endpoints
    protected string $chatCompletionsUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $responsesUrl = 'https://api.openai.com/v1/responses';

    // Модели, которые используют Responses API (reasoning models)
    protected array $responsesApiModels = [
        'gpt-5',
        'o1',
        'o1-mini',
        'o1-preview',
        'o3',
        'o3-mini',
    ];

    // Лимиты токенов для разных моделей
    protected array $modelMaxTokens = [
        'gpt-5' => 100000,
        'o1' => 100000,
        'o3' => 100000,
        'gpt-4o' => 16384,
        'gpt-4o-mini' => 16384,
        'gpt-4-turbo' => 4096,
        'gpt-4' => 4096,
        'gpt-3.5-turbo' => 4096,
    ];

    public function __construct()
    {
        $this->apiKey = Setting::getChatGptApiKey() ?? '';
        $this->model = Setting::getChatGptModel();
    }

    /**
     * Проверяет, использует ли модель Responses API
     */
    protected function usesResponsesApi(): bool
    {
        return in_array($this->model, $this->responsesApiModels);
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

        Log::info('ChatGPT request starting', [
            'model' => $this->model,
            'api_type' => $this->usesResponsesApi() ? 'responses' : 'chat_completions',
        ]);

        try {
            if ($this->usesResponsesApi()) {
                return $this->generateWithResponsesApi($promptText);
            } else {
                return $this->generateWithChatCompletionsApi($promptText);
            }
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
     * Генерация через Responses API (для GPT-5, o1, o3 и других reasoning моделей)
     */
    protected function generateWithResponsesApi(string $promptText): array
    {
        $fullPrompt = $this->getResponsesApiPrompt() . "\n\n" . $promptText;

        $requestBody = [
            'model' => $this->model,
            'input' => $fullPrompt,
        ];

        Log::info('Responses API request', [
            'model' => $this->model,
            'prompt_length' => strlen($fullPrompt),
        ]);

        $response = Http::timeout(900)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->responsesUrl, $requestBody);

        if (!$response->successful()) {
            Log::error('Responses API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка Responses API: ' . $response->status() . ' - ' . $response->body(),
                'files' => [],
            ];
        }

        $data = $response->json();

        // Responses API возвращает output_text
        $content = $data['output_text'] ?? '';

        // Также проверяем альтернативные поля
        if (empty($content) && isset($data['output'])) {
            $content = is_array($data['output']) ? json_encode($data['output']) : $data['output'];
        }

        $usage = $data['usage'] ?? [];

        Log::info('Responses API response received', [
            'tokens_used' => $usage,
            'content_length' => strlen($content),
            'response_keys' => array_keys($data),
        ]);

        if (empty(trim($content))) {
            $status = $data['status'] ?? 'unknown';
            return [
                'success' => false,
                'message' => "Модель вернула пустой ответ. Статус: {$status}. Возможно запрос ещё обрабатывается.",
                'files' => [],
                'raw_response' => $data,
            ];
        }

        // Парсим ответ в файлы (формат ===filename=== ... ===filename-end===)
        $files = $this->parseResponsesApiFiles($content);

        if (empty($files)) {
            $preview = mb_substr($content, 0, 300);
            Log::error('Failed to parse Responses API files', [
                'content_preview' => $preview,
                'content_length' => strlen($content),
            ]);
            return [
                'success' => false,
                'message' => 'Не удалось разобрать ответ на файлы. Начало ответа: "' . $preview . '..."',
                'files' => [],
                'raw_response' => $content,
            ];
        }

        return [
            'success' => true,
            'message' => 'Код сайта успешно сгенерирован',
            'files' => $files,
            'tokens_used' => $usage,
        ];
    }

    /**
     * Генерация через Chat Completions API (для GPT-4o, GPT-4 и других обычных моделей)
     */
    protected function generateWithChatCompletionsApi(string $promptText): array
    {
        $systemPrompt = $this->getChatCompletionsPrompt();

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

        $maxTokens = $this->getMaxTokens();

        // Новые модели используют max_completion_tokens
        if (in_array($this->model, ['gpt-4o', 'gpt-4o-mini'])) {
            if ($maxTokens !== null) {
                $requestBody['max_completion_tokens'] = $maxTokens;
            }
            $requestBody['temperature'] = 0.7;
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
            ->post($this->chatCompletionsUrl, $requestBody);

        if (!$response->successful()) {
            Log::error('Chat Completions API error', [
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
        $usage = $data['usage'] ?? [];

        Log::info('Chat Completions response received', [
            'tokens_used' => $usage,
            'content_length' => strlen($content),
        ]);

        if (empty(trim($content))) {
            return [
                'success' => false,
                'message' => 'Модель вернула пустой ответ',
                'files' => [],
            ];
        }

        // Парсим ответ в файлы (формат --- FILE: filename ---)
        $files = $this->parseChatCompletionsFiles($content);

        if (empty($files)) {
            $preview = mb_substr($content, 0, 200);
            return [
                'success' => false,
                'message' => 'Не удалось разобрать ответ на файлы. Модель вернула: "' . $preview . '..."',
                'files' => [],
                'raw_response' => $content,
            ];
        }

        return [
            'success' => true,
            'message' => 'Код сайта успешно сгенерирован',
            'files' => $files,
            'tokens_used' => $usage,
        ];
    }

    /**
     * Промпт для Responses API (GPT-5, o1, o3)
     */
    protected function getResponsesApiPrompt(): string
    {
        return <<<'PROMPT'
Ты — опытный senior full-stack веб-разработчик, SEO-специалист и UX-дизайнер.
Твоя задача — сгенерировать полностью готовый, уникальный, production-ready веб-сайт.

Технические требования:
- Чистый, валидный HTML5
- Tailwind CSS (подключён через CDN)
- Адаптивность (mobile / tablet / desktop)
- SEO-оптимизация: корректные title, description, h1-h3
- Семантическая разметка

Изображения:
- Используй ТОЛЬКО реальные изображения с Unsplash в формате:
  https://images.unsplash.com/photo-[ID]?w=800&h=600&fit=crop&auto=format
- Примеры рабочих ID фотографий: 1560472354-b33ca0d9ccd4, 1522202176988-66273c2fd55f, 1497366216548-37526070297c, 1553877522-43269d4ea984, 1460925895917-afdab827c52f
- Каждое изображение должно соответствовать тематике страницы
- Обязательно указывай alt-текст с описанием изображения
- НЕ используй placeholder изображения или via.placeholder.com

Контент:
- Тексты должны быть уникальными, логичными
- Без placeholder-текста типа "Lorem ipsum"
- Стиль — профессиональный

Формат ответа:
- НЕ добавляй никаких комментариев, объяснений или пояснений
- В ответе должны быть ТОЛЬКО файлы сайта
- Каждый файл обязан быть оформлен строго так:

===filename===
(контент файла)
===filename-end===

Пример:
===index.html===
<!DOCTYPE html>
<html>...</html>
===index.html-end===

===css/style.css===
body { margin: 0; }
===css/style.css-end===

Правила:
1. Всегда начинай с index.html как главный файл
2. Создай все необходимые файлы (HTML, CSS, JS)
3. Используй относительные пути для ссылок
4. Никаких упоминаний ИИ или ChatGPT
PROMPT;
    }

    /**
     * Промпт для Chat Completions API (GPT-4o, GPT-4)
     */
    protected function getChatCompletionsPrompt(): string
    {
        return <<<'PROMPT'
You are a professional web developer. Generate complete website code based on user requirements.

IMPORTANT: Output format must be EXACTLY as follows for each file:

--- FILE: filename.html ---
[file content here]

--- FILE: css/style.css ---
[file content here]

--- FILE: js/script.js ---
[file content here]

Images:
- Use ONLY real Unsplash images in format: https://images.unsplash.com/photo-[ID]?w=800&h=600&fit=crop&auto=format
- Example working photo IDs: 1560472354-b33ca0d9ccd4, 1522202176988-66273c2fd55f, 1497366216548-37526070297c
- Each image must match the page theme
- Always include descriptive alt text
- Do NOT use placeholder images or via.placeholder.com

Rules:
1. Always start with index.html as the main file
2. Use this exact format: --- FILE: path/filename.ext ---
3. Create all necessary files (HTML, CSS, JS)
4. Include proper HTML5 structure
5. Make responsive design with CSS (use Tailwind CDN)
6. Do not include any explanation text, only file contents
7. Each file must start with --- FILE: and end before the next --- FILE:
8. Use relative paths for all links and includes
PROMPT;
    }

    /**
     * Парсинг файлов из Responses API (формат ===filename=== ... ===filename-end===)
     */
    protected function parseResponsesApiFiles(string $content): array
    {
        $files = [];

        // Сначала unescape JSON-escaped символы (\n, \t, \", etc.)
        $content = $this->unescapeJsonContent($content);

        // Паттерн: ===filename=== ... ===filename-end===
        $pattern = '/===([^=]+?)===([\s\S]*?)===\1-end===/';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $filename = trim($match[1]);
            $fileContent = trim($match[2]);

            if (!empty($filename) && !empty($fileContent)) {
                $fileContent = $this->cleanFileContent($fileContent);

                // Заменяем ссылки .html на .php внутри контента
                $fileContent = $this->replaceHtmlLinksToPhp($fileContent);

                // Переименовываем .html файлы в .php
                if (str_ends_with($filename, '.html')) {
                    $filename = preg_replace('/\.html$/', '.php', $filename);
                }

                $files[$filename] = $fileContent;
            }
        }

        Log::info('Parsed Responses API files', ['files_count' => count($files), 'filenames' => array_keys($files)]);

        return $files;
    }

    /**
     * Unescape JSON-escaped контент (включая Unicode)
     */
    protected function unescapeJsonContent(string $content): string
    {
        // Проверяем наличие escape-последовательностей
        if (str_contains($content, '\\u') || str_contains($content, '\\n') || str_contains($content, '\\/')) {
            // Метод 1: Декодируем как JSON строку
            // Экранируем кавычки внутри контента и оборачиваем
            $jsonString = '"' . str_replace('"', '\\"', $content) . '"';
            $decoded = json_decode($jsonString);

            if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // Метод 2: Используем preg_replace для Unicode
            $content = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16BE');
            }, $content);

            // Заменяем остальные escape-последовательности
            $content = str_replace(
                ['\\/', '\\n', '\\t', '\\r', '\\"', "\\'", '\\\\'],
                ['/', "\n", "\t", "\r", '"', "'", '\\'],
                $content
            );
        }

        return $content;
    }

    /**
     * Парсинг файлов из Chat Completions API (формат --- FILE: filename ---)
     */
    protected function parseChatCompletionsFiles(string $content): array
    {
        $files = [];

        // Паттерн для поиска файлов: --- FILE: filename ---
        $pattern = '/---\s*FILE:\s*([^\s-]+(?:\/[^\s-]+)*)\s*---/i';

        // Разбиваем контент по маркерам файлов
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        for ($i = 1; $i < count($parts) - 1; $i += 2) {
            $filename = trim($parts[$i]);
            $fileContent = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';

            if (!empty($filename) && !empty($fileContent)) {
                $fileContent = $this->cleanFileContent($fileContent);

                // Заменяем ссылки .html на .php внутри контента
                $fileContent = $this->replaceHtmlLinksToPhp($fileContent);

                // Переименовываем .html файлы в .php
                if (str_ends_with($filename, '.html')) {
                    $filename = preg_replace('/\.html$/', '.php', $filename);
                }

                $files[$filename] = $fileContent;
            }
        }

        return $files;
    }

    /**
     * Замена ссылок .html на .php внутри контента
     */
    protected function replaceHtmlLinksToPhp(string $content): string
    {
        // Заменяем href="*.html" на href="*.php"
        $content = preg_replace('/href="([^"]*?)\.html([^"]*)"/', 'href="$1.php$2"', $content);

        // Заменяем href='*.html' на href='*.php'
        $content = preg_replace("/href='([^']*?)\.html([^']*)'/", "href='\$1.php\$2'", $content);

        // Заменяем src="*.html" на src="*.php" (редко, но на всякий случай)
        $content = preg_replace('/src="([^"]*?)\.html([^"]*)"/', 'src="$1.php$2"', $content);

        // Заменяем action="*.html" для форм
        $content = preg_replace('/action="([^"]*?)\.html([^"]*)"/', 'action="$1.php$2"', $content);

        return $content;
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

        // Собираем список .php файлов для sitemap
        $phpFiles = [];

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

            // Собираем .php файлы для sitemap (только в корне)
            if (str_ends_with($filename, '.php') && !str_contains($filename, '/')) {
                $phpFiles[] = $filename;
            }
        }

        // Генерируем sitemap.xml
        $sitemap = $this->generateSitemap($phpFiles);
        Storage::disk('local')->put($storagePath . '/sitemap.xml', $sitemap);
        $totalSize += strlen($sitemap);

        // Генерируем robots.txt
        $robots = $this->generateRobotsTxt();
        Storage::disk('local')->put($storagePath . '/robots.txt', $robots);
        $totalSize += strlen($robots);

        return [
            'storage_path' => $storagePath,
            'files_count' => count($files) + 2, // +2 for sitemap and robots
            'total_size' => $totalSize,
        ];
    }

    /**
     * Генерация sitemap.xml
     */
    protected function generateSitemap(array $phpFiles): string
    {
        $lastmod = date('Y-m-d', strtotime('-10 days'));

        $urls = '';
        foreach ($phpFiles as $file) {
            $loc = $file === 'index.php' ? '{domain}/' : '{domain}/' . str_replace('.php', '.php', $file);
            $priority = $file === 'index.php' ? '1.0' : '0.8';

            $urls .= <<<XML

    <url>
        <loc>https://{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>{$priority}</priority>
    </url>
XML;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">{$urls}
</urlset>
XML;
    }

    /**
     * Генерация robots.txt
     */
    protected function generateRobotsTxt(): string
    {
        return <<<TXT
User-agent: *
Allow: /

Disallow: /admin/

Sitemap: https://{domain}/sitemap.xml
TXT;
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
