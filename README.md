# OpenRouter PHP SDK

PHP 8 SDK для работы с OpenRouter API. Библиотека предоставляет удобный интерфейс для взаимодействия с API OpenRouter, включая поддержку reasoning capabilities и streaming responses.

## Требования

- PHP 8.0 или выше
- Composer
- Guzzle HTTP Client

## Установка

```bash
composer require j0sky/openrouter-client
```

Или добавьте в ваш `composer.json`:

```json
{
    "require": {
        "j0sky/openrouter-client": "master"
    }
}
```

## Быстрый старт

### Базовое использование

```php
<?php

use OpenRouter\Client;
use OpenRouter\DTO\Request\Message;

require_once 'vendor/autoload.php';

// Создаем клиент
$client = new Client('your-api-key-here');

// Простой запрос
$response = $client->chat(
    'openai/gpt-4o',
    'Привет! Как дела?'
);

echo $response->getContent();
```

### Использование с несколькими сообщениями

```php
<?php

use OpenRouter\Client;
use OpenRouter\DTO\Request\Message;

$client = new Client('your-api-key-here');

$messages = [
    new Message('system', 'Ты полезный ассистент.'),
    new Message('user', 'Напиши рецепт вегетарианской лазаньи на 4 персоны.'),
];

$response = $client->chat('openai/gpt-4o', $messages);

echo $response->getContent();
```

### Использование Reasoning API

```php
<?php

use OpenRouter\Client;
use OpenRouter\DTO\Request\Message;
use OpenRouter\DTO\Request\ReasoningConfig;

$client = new Client('your-api-key-here');

$messages = [
    new Message('user', 'Реши эту математическую задачу: 2x + 5 = 15'),
];

$response = $client->chat(
    'anthropic/claude-3.7-sonnet:thinking',
    $messages,
    [
        'reasoning' => new ReasoningConfig(
            maxTokens: 1000,
            effort: 'high',
            encrypted: false
        ),
    ]
);

echo $response->getContent();
```

### Использование DTO напрямую

```php
<?php

use OpenRouter\Client;
use OpenRouter\DTO\Request\Message;
use OpenRouter\DTO\Request\ReasoningConfig;
use OpenRouter\DTO\Request\ResponsesRequest;

$client = new Client('your-api-key-here');

$request = new ResponsesRequest(
    model: 'openai/gpt-4o',
    messages: [
        new Message('user', 'Привет!'),
    ],
    stream: false,
    reasoning: new ReasoningConfig(
        maxTokens: 500,
        effort: 'medium'
    ),
    extraBody: [
        'temperature' => 0.7,
        'max_tokens' => 1000,
    ]
);

$response = $client->responses($request);

// Получить первое сообщение
echo $response->getContent();

// Получить все choices
foreach ($response->getChoices() as $choice) {
    echo "Choice ID: " . $choice->getId() . "\n";
    echo "Content: " . $choice->getContent() . "\n";
    echo "Finish Reason: " . $choice->getFinishReason() . "\n";
}

// Получить статистику использования
if ($usage = $response->getUsage()) {
    echo "Prompt Tokens: " . $usage->getPromptTokens() . "\n";
    echo "Completion Tokens: " . $usage->getCompletionTokens() . "\n";
    echo "Total Tokens: " . $usage->getTotalTokens() . "\n";
    if ($cost = $usage->getCost()) {
        echo "Cost: $" . $cost . "\n";
    }
}
```

### Streaming ответов

```php
<?php

use OpenRouter\Client;
use OpenRouter\DTO\Request\Message;

$client = new Client('your-api-key-here');

$messages = [
    new Message('user', 'Расскажи короткую историю о космосе.'),
];

foreach ($client->chatStream('openai/gpt-4o', $messages) as $event) {
    // $event содержит полный объект ответа от API
    $textDelta = $event['output_text_delta']
        ?? ($event['output_text'] ?? null)
        ?? ($event['output'][0]['content'][0]['text'] ?? null)
        ?? ($event['choices'][0]['delta']['content'] ?? null); // для совместимости со старыми форматами

    if ($textDelta !== null) {
        echo $textDelta;
        flush();
    }
}
```

### Обработка ошибок

```php
<?php

use OpenRouter\Client;
use OpenRouter\Exceptions\ApiException;
use OpenRouter\Exceptions\NetworkException;

$client = new Client('your-api-key-here');

try {
    $response = $client->chat('openai/gpt-4o', 'Привет!');
    echo $response->getContent();
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
    if ($details = $e->getErrorDetails()) {
        print_r($details);
    }
} catch (NetworkException $e) {
    echo "Network Error: " . $e->getMessage() . "\n";
}
```

## API Reference

### Client

#### `__construct(string $apiKey, ?string $baseUri = null)`

Создает новый экземпляр клиента.

- `$apiKey` - Ваш API ключ OpenRouter
- `$baseUri` - Опциональный базовый URI (по умолчанию: `https://openrouter.ai/api/v1`)

#### `responses(ResponsesRequest $request): ResponsesResponse`

Отправляет запрос к `/api/v1/responses` endpoint.

#### `chat(string $model, string|array $messages, array $options = []): ResponsesResponse`

Удобный метод для отправки чат-запроса.

- `$model` - Идентификатор модели (например, `'openai/gpt-4o'`)
- `$messages` - Сообщения (строка для одного сообщения или массив `Message` объектов)
- `$options` - Дополнительные опции:
  - `stream` (bool) - Включить streaming
  - `reasoning` (ReasoningConfig) - Конфигурация reasoning
  - `extraBody` (array) - Дополнительные параметры тела запроса
  - `providerOptions` (array) - Опции провайдера

#### `chatStream(string $model, string|array $messages, array $options = []): \Generator`

Отправляет streaming запрос и возвращает генератор для получения чанков.

### DTO Classes

#### `Message`

Представляет сообщение в разговоре.

```php
new Message(
    role: 'user', // 'system', 'user', или 'assistant'
    content: 'Текст сообщения',
    providerOptions: [] // Опциональные опции провайдера
)
```

#### `ReasoningConfig`

Конфигурация для reasoning capabilities.

```php
new ReasoningConfig(
    maxTokens: 1000,    // Максимальное количество токенов
    effort: 'high',     // 'low', 'medium', или 'high'
    encrypted: false    // Шифровать ли reasoning chain
)
```

#### `ResponsesRequest`

DTO для запроса к `/api/v1/responses`.

#### `ResponsesResponse`

DTO для ответа от API.

## Поддерживаемые модели

OpenRouter поддерживает более 300 моделей. Полный список доступен на [OpenRouter Models](https://openrouter.ai/models).

Примеры популярных моделей:
- `openai/gpt-4o`
- `openai/gpt-3.5-turbo`
- `anthropic/claude-3.7-sonnet:thinking`
- `google/gemini-pro`
- `meta-llama/llama-3.1-70b-instruct`

## Лицензия

MIT

## Вклад

Приветствуются pull requests и issues!

