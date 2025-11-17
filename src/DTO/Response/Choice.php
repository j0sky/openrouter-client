<?php

declare(strict_types=1);

namespace OpenRouter\DTO\Response;

/**
 * Represents a choice (or output message) in the API response
 */
class Choice
{
    /**
     * @param string $id Unique identifier for the choice
     * @param string $role The role of the message
     * @param string|null $content The flattened textual content of the message
     * @param string|null $finishReason Reason why the response finished
     * @param array<string, mixed> $metadata Additional metadata
     * @param array<int, mixed> $rawContent Raw content blocks as returned by the API
     */
    public function __construct(
        private readonly string $id,
        private readonly string $role,
        private readonly ?string $content = null,
        private readonly ?string $finishReason = null,
        private readonly array $metadata = [],
        private readonly array $rawContent = []
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

    /**
     * @return array<int, mixed>
     */
    public function getRawContent(): array
    {
        return $this->rawContent;
    }

    public static function fromArray(array $data): self
    {
        if (isset($data['message'])) {
            $message = $data['message'];
            $rawContent = self::normalizeRawContent($message['content'] ?? null);

            return new self(
                $data['id'] ?? '',
                $message['role'] ?? ($data['role'] ?? 'assistant'),
                self::stringifyContent($message['content'] ?? null),
                $data['finish_reason'] ?? null,
                $data['metadata'] ?? [],
                $rawContent
            );
        }

        $rawContent = self::normalizeRawContent($data['content'] ?? null);

        return new self(
            $data['id'] ?? '',
            $data['role'] ?? 'assistant',
            self::stringifyContent($data['content'] ?? null),
            $data['finish_reason'] ?? ($data['status'] ?? null),
            $data['metadata'] ?? [],
            $rawContent
        );
    }

    /**
     * @param string|array<int, mixed>|null $content
     */
    private static function stringifyContent(string|array|null $content): ?string
    {
        if (is_string($content)) {
            return $content;
        }

        if (!is_array($content)) {
            return null;
        }

        $parts = [];

        foreach ($content as $item) {
            if (is_string($item) && $item !== '') {
                $parts[] = $item;
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            if (isset($item['text']) && is_string($item['text'])) {
                $parts[] = $item['text'];
                continue;
            }

            if (isset($item['content'])) {
                $nested = self::stringifyContent($item['content']);
                if ($nested !== null && $nested !== '') {
                    $parts[] = $nested;
                }
            }
        }

        if (empty($parts)) {
            return null;
        }

        return implode("\n", $parts);
    }

    /**
     * @param mixed $content
     * @return array<int, mixed>
     */
    private static function normalizeRawContent(mixed $content): array
    {
        if (is_array($content)) {
            return $content;
        }

        if (is_string($content)) {
            return [$content];
        }

        return [];
    }
}

