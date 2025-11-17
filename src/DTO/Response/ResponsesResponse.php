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
     * @param Choice[] $choices Array of choices (legacy + synthesized from output)
     * @param Usage|null $usage Usage statistics
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $outputText Flat output_text field provided by the API
     * @param array<int, array<string, mixed>> $output Raw output blocks as returned by the API
     * @param array<string, mixed> $rawResponse Entire decoded response body
     */
    public function __construct(
        private readonly string $id,
        private readonly array $choices,
        private readonly ?Usage $usage = null,
        private readonly array $metadata = [],
        private readonly ?string $outputText = null,
        private readonly array $output = [],
        private readonly array $rawResponse = []
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

    public function getOutputText(): ?string
    {
        return $this->outputText;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }

    /**
     * Get the main textual content of the response (helper for backwards compatibility)
     */
    public function getContent(): ?string
    {
        if ($this->outputText !== null && $this->outputText !== '') {
            return $this->outputText;
        }

        if (!empty($this->output)) {
            foreach ($this->output as $outputItem) {
                if (($outputItem['type'] ?? null) !== 'message') {
                    continue;
                }

                $choice = Choice::fromArray($outputItem);
                $content = $choice->getContent();
                if ($content !== null && $content !== '') {
                    return $content;
                }
            }
        }

        if (empty($this->choices)) {
            return null;
        }

        return $this->choices[0]->getContent();
    }

    public static function fromArray(array $data): self
    {
        $output = $data['output'] ?? [];

        $choiceSource = $data['choices'] ?? null;
        if (empty($choiceSource) && !empty($output)) {
            $choiceSource = array_values(
                array_filter(
                    $output,
                    fn(array $item) => ($item['type'] ?? null) === 'message'
                )
            );
        }

        $choices = array_map(
            fn(array $choice) => Choice::fromArray($choice),
            $choiceSource ?? []
        );

        $usage = isset($data['usage']) && is_array($data['usage'])
            ? Usage::fromArray($data['usage'])
            : null;

        return new self(
            $data['id'] ?? '',
            $choices,
            $usage,
            $data['metadata'] ?? [],
            $data['output_text'] ?? null,
            $output,
            $data
        );
    }
}

