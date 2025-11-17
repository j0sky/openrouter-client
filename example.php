<?php

/**
 * Пример использования OpenRouter PHP SDK
 * 
 * Перед запуском установите зависимости:
 * composer install
 * 
 * И укажите ваш API ключ OpenRouter
 */

require_once __DIR__ . '/vendor/autoload.php';

use OpenRouter\Client;
use OpenRouter\DTO\Request\Message;
use OpenRouter\DTO\Request\ReasoningConfig;
use OpenRouter\Exceptions\ApiException;
use OpenRouter\Exceptions\NetworkException;

// Замените на ваш API ключ
$apiKey = getenv('OPENROUTER_API_KEY') ?: 'your-api-key-here';

try {
    $client = new Client($apiKey);

    echo "=== Пример 1: Простой запрос ===\n\n";
    
    $response = $client->chat(
        'openai/gpt-3.5-turbo',
        'Привет! Напиши одно предложение о PHP.'
    );
    
    echo "Ответ: " . $response->getContent() . "\n\n";
    
    if ($usage = $response->getUsage()) {
        echo "Использовано токенов: " . $usage->getTotalTokens() . "\n";
        if ($cost = $usage->getCost()) {
            echo "Стоимость: $" . number_format($cost, 6) . "\n";
        }
    }
    
    echo "\n=== Пример 2: Запрос с несколькими сообщениями ===\n\n";
    
    $messages = [
        new Message('system', 'Ты полезный ассистент, который отвечает кратко.'),
        new Message('user', 'Что такое OpenRouter?'),
    ];
    
    $response = $client->chat('openai/gpt-3.5-turbo', $messages);
    echo "Ответ: " . $response->getContent() . "\n\n";
    
    echo "=== Пример 3: Reasoning API (требует поддерживающую модель) ===\n\n";
    
    $response = $client->chat(
        'anthropic/claude-3.7-sonnet:thinking',
        'Реши эту задачу: Если у меня есть 5 яблок и я отдаю 2 другу, сколько у меня останется?',
        [
            'reasoning' => new ReasoningConfig(
                maxTokens: 500,
                effort: 'medium',
                encrypted: false
            ),
        ]
    );
    
    echo "Ответ: " . $response->getContent() . "\n\n";
    
    echo "=== Пример 4: Streaming ответов ===\n\n";
    
    echo "Ответ (streaming): ";
    foreach ($client->chatStream('openai/gpt-3.5-turbo', 'Сосчитай от 1 до 5') as $event) {
        $textDelta = $event['output_text_delta']
            ?? ($event['output_text'] ?? null)
            ?? ($event['output'][0]['content'][0]['text'] ?? null)
            ?? ($event['choices'][0]['delta']['content'] ?? null);

        if ($textDelta !== null) {
            echo $textDelta;
            flush();
        }
    }
    echo "\n\n";
    
} catch (ApiException $e) {
    echo "Ошибка API: " . $e->getMessage() . "\n";
    if ($details = $e->getErrorDetails()) {
        echo "Детали: ";
        print_r($details);
    }
} catch (NetworkException $e) {
    echo "Ошибка сети: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}

