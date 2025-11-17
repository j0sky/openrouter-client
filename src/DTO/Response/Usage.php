<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Response;

/**
 * Usage statistics for the API call
 */
class Usage
{
    /**
     * @param int $promptTokens Number of tokens in the prompt
     * @param int $completionTokens Number of tokens in the completion
     * @param int $totalTokens Total number of tokens
     * @param float|null $cost Cost of the request in USD
     */
    public function __construct(
        private readonly int $promptTokens,
        private readonly int $completionTokens,
        private readonly int $totalTokens,
        private readonly ?float $cost = null
    ) {
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['prompt_tokens'] ?? 0,
            $data['completion_tokens'] ?? 0,
            $data['total_tokens'] ?? 0,
            $data['cost'] ?? null
        );
    }
}

