<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Request;

use JsonSerializable;

/**
 * Configuration for reasoning capabilities
 */
class ReasoningConfig implements JsonSerializable
{
    /**
     * @param int|null $maxTokens Maximum tokens for reasoning
     * @param string|null $effort Effort level (low, medium, high)
     * @param bool|null $encrypted Whether to encrypt reasoning chain
     */
    public function __construct(
        private readonly ?int $maxTokens = null,
        private readonly ?string $effort = null,
        private readonly ?bool $encrypted = null
    ) {
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function getEffort(): ?string
    {
        return $this->effort;
    }

    public function getEncrypted(): ?bool
    {
        return $this->encrypted;
    }

    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->maxTokens !== null) {
            $data['max_tokens'] = $this->maxTokens;
        }

        if ($this->effort !== null) {
            $data['effort'] = $this->effort;
        }

        if ($this->encrypted !== null) {
            $data['encrypted'] = $this->encrypted;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['max_tokens'] ?? null,
            $data['effort'] ?? null,
            $data['encrypted'] ?? null
        );
    }
}

