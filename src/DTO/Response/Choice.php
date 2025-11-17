<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Response;

/**
 * Represents a choice in the API response
 */
class Choice
{
    /**
     * @param string $id Unique identifier for the choice
     * @param string $role The role of the message
     * @param string|null $content The content of the message
     * @param string|null $finishReason Reason why the response finished
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        private readonly string $id,
        private readonly string $role,
        private readonly ?string $content = null,
        private readonly ?string $finishReason = null,
        private readonly array $metadata = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getFinishReason(): ?string
    {
        return $this->finishReason;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public static function fromArray(array $data): self
    {
        $message = $data['message'] ?? [];
        
        return new self(
            $data['id'] ?? '',
            $message['role'] ?? 'assistant',
            $message['content'] ?? null,
            $data['finish_reason'] ?? null,
            $data['metadata'] ?? []
        );
    }
}

