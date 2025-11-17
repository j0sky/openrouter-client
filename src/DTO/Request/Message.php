<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Request;

use JsonSerializable;

/**
 * Represents a message in the conversation
 */
class Message implements JsonSerializable
{
    /**
     * @param string $role The role of the message (system, user, assistant)
     * @param string|array $content The content of the message
     * @param array<string, mixed> $providerOptions Optional provider-specific options
     */
    public function __construct(
        private readonly string $role,
        private readonly string|array $content,
        private readonly array $providerOptions = []
    ) {
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getContent(): string|array
    {
        return $this->content;
    }

    public function getProviderOptions(): array
    {
        return $this->providerOptions;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'role' => $this->role,
            'content' => $this->content,
        ];

        if (!empty($this->providerOptions)) {
            $data['provider_options'] = $this->providerOptions;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['role'],
            $data['content'] ?? '',
            $data['provider_options'] ?? []
        );
    }
}

