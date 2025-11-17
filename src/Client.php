<?php

declare(strict_types=1);

namespace OpenRouter;

use OpenRouter\DTO\Request\Message;
use OpenRouter\DTO\Request\ReasoningConfig;
use OpenRouter\DTO\Request\ResponsesRequest;
use OpenRouter\DTO\Response\ResponsesResponse;
use OpenRouter\Exceptions\ApiException;
use OpenRouter\Exceptions\NetworkException;
use OpenRouter\Http\HttpClient;

/**
 * Main client for OpenRouter API
 */
class Client
{
    private HttpClient $httpClient;

    /**
     * @param string $apiKey Your OpenRouter API key
     * @param string|null $baseUri Optional custom base URI (defaults to https://openrouter.ai)
     */
    public function __construct(string $apiKey, ?string $baseUri = null)
    {
        $this->httpClient = new HttpClient($apiKey, $baseUri ?? 'https://openrouter.ai/');
    }

    /**
     * Send a request to the /api/v1/responses endpoint
     *
     * @param ResponsesRequest $request The request DTO
     * @return ResponsesResponse The response DTO
     * @throws ApiException
     * @throws NetworkException
     */
    public function responses(ResponsesRequest $request): ResponsesResponse
    {
        $data = $this->httpClient->post('/api/v1/responses', $request->jsonSerialize());
        return ResponsesResponse::fromArray($data);
    }

    /**
     * Convenience method to create a simple chat request
     *
     * @param string $model The model identifier
     * @param string|Message[] $messages The messages (string for single user message, or array of Message objects)
     * @param array<string, mixed> $options Additional options (stream, reasoning, extraBody, etc.)
     * @return ResponsesResponse
     * @throws ApiException
     * @throws NetworkException
     */
    public function chat(string $model, string|array $messages, array $options = []): ResponsesResponse
    {
        // Convert string to Message array if needed
        if (is_string($messages)) {
            $messages = [new Message('user', $messages)];
        } elseif (!empty($messages) && !($messages[0] instanceof Message)) {
            // Convert array of arrays to Message objects
            $messages = array_map(
                fn(array $msg) => Message::fromArray($msg),
                $messages
            );
        }

        $reasoning = isset($options['reasoning']) && is_array($options['reasoning'])
            ? ReasoningConfig::fromArray($options['reasoning'])
            : ($options['reasoning'] ?? null);

        $request = new ResponsesRequest(
            model: $model,
            messages: $messages,
            extraBody: $options['extraBody'] ?? [],
            stream: $options['stream'] ?? false,
            reasoning: $reasoning,
            providerOptions: $options['providerOptions'] ?? []
        );

        return $this->responses($request);
    }

    /**
     * Stream a chat response
     *
     * @param string $model The model identifier
     * @param string|Message[] $messages The messages
     * @param array<string, mixed> $options Additional options
     * @return \Generator<array<string, mixed>> Generator yielding parsed SSE events
     * @throws ApiException
     * @throws NetworkException
     */
    public function chatStream(string $model, string|array $messages, array $options = []): \Generator
    {
        // Convert string to Message array if needed
        if (is_string($messages)) {
            $messages = [new Message('user', $messages)];
        } elseif (!empty($messages) && !($messages[0] instanceof Message)) {
            // Convert array of arrays to Message objects
            $messages = array_map(
                fn(array $msg) => Message::fromArray($msg),
                $messages
            );
        }

        $reasoning = isset($options['reasoning']) && is_array($options['reasoning'])
            ? ReasoningConfig::fromArray($options['reasoning'])
            : ($options['reasoning'] ?? null);

        $request = new ResponsesRequest(
            model: $model,
            messages: $messages,
            extraBody: $options['extraBody'] ?? [],
            stream: true,
            reasoning: $reasoning,
            providerOptions: $options['providerOptions'] ?? []
        );

        foreach ($this->httpClient->postStream('/api/v1/responses', $request->jsonSerialize()) as $event) {
            yield $event;
        }
    }
}

