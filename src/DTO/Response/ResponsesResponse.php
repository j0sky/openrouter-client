<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Response;

/**
 * Response DTO for /api/v1/responses endpoint
 */
class ResponsesResponse
{
    /**
     * @param string $id Unique identifier for the response
     * @param Choice[] $choices Array of choices
     * @param Usage|null $usage Usage statistics
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        private readonly string $id,
        private readonly array $choices,
        private readonly ?Usage $usage = null,
        private readonly array $metadata = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Choice[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getUsage(): ?Usage
    {
        return $this->usage;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get the first choice content (convenience method)
     */
    public function getContent(): ?string
    {
        if (empty($this->choices)) {
            return null;
        }

        return $this->choices[0]->getContent();
    }

    public static function fromArray(array $data): self
    {
        $choices = array_map(
            fn(array $choice) => Choice::fromArray($choice),
            $data['choices'] ?? []
        );

        $usage = isset($data['usage']) && is_array($data['usage'])
            ? Usage::fromArray($data['usage'])
            : null;

        return new self(
            $data['id'] ?? '',
            $choices,
            $usage,
            $data['metadata'] ?? []
        );
    }
}

