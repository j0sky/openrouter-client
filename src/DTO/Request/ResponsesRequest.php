<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Request;

use JsonSerializable;

/**
 * Request DTO for /api/v1/responses endpoint
 */
class ResponsesRequest implements JsonSerializable
{
    /**
     * @param string $model The model identifier (e.g., "openai/gpt-4o", "anthropic/claude-3.7-sonnet:thinking")
     * @param Message[] $messages Array of messages in the conversation
     * @param array<string, mixed> $extraBody Additional body parameters
     * @param bool $stream Whether to stream the response
     * @param ReasoningConfig|null $reasoning Reasoning configuration
     * @param array<string, mixed> $providerOptions Provider-specific options
     */
    public function __construct(
        private readonly string $model,
        private readonly array $messages,
        private readonly array $extraBody = [],
        private readonly bool $stream = false,
        private readonly ?ReasoningConfig $reasoning = null,
        private readonly array $providerOptions = []
    ) {
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getExtraBody(): array
    {
        return $this->extraBody;
    }

    public function isStream(): bool
    {
        return $this->stream;
    }

    public function getReasoning(): ?ReasoningConfig
    {
        return $this->reasoning;
    }

    public function getProviderOptions(): array
    {
        return $this->providerOptions;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'model' => $this->model,
            'messages' => array_map(
                fn(Message $message) => $message->jsonSerialize(),
                $this->messages
            ),
        ];

        if ($this->stream) {
            $data['stream'] = true;
        }

        if ($this->reasoning !== null) {
            $data['reasoning'] = $this->reasoning->jsonSerialize();
        }

        // Merge extra body parameters
        $data = array_merge($data, $this->extraBody);

        // Merge provider options if any
        if (!empty($this->providerOptions)) {
            $data = array_merge($data, $this->providerOptions);
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        $messages = array_map(
            fn(array $msg) => Message::fromArray($msg),
            $data['messages'] ?? []
        );

        $reasoning = isset($data['reasoning']) && is_array($data['reasoning'])
            ? ReasoningConfig::fromArray($data['reasoning'])
            : null;

        return new self(
            $data['model'],
            $messages,
            $data['extra_body'] ?? [],
            $data['stream'] ?? false,
            $reasoning,
            $data['provider_options'] ?? []
        );
    }
}

