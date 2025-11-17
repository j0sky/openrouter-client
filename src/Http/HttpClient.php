<?php

declare(strict_types=1);

namespace OpenRouter\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OpenRouter\Exceptions\ApiException;
use OpenRouter\Exceptions\NetworkException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client wrapper for OpenRouter API
 */
class HttpClient
{
    private Client $client;
    private string $apiKey;
    private string $baseUri;

    public function __construct(string $apiKey, string $baseUri = 'https://openrouter.ai/api/v1')
    {
        $this->apiKey = $apiKey;
        $this->baseUri = $baseUri;
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 60.0,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'https://github.com/j0sky/openrouter-client',
                'X-Title' => 'OpenRouter PHP Client',
            ],
        ]);
    }

    /**
     * Send a POST request to the API
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data The request data
     * @return array<string, mixed> The response data
     * @throws ApiException
     * @throws NetworkException
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new NetworkException(
                'Network error occurred: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Send a streaming POST request to the API
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data The request data
     * @return \Generator<array<string, mixed>> Generator yielding parsed SSE events
     * @throws ApiException
     * @throws NetworkException
     */
    public function postStream(string $endpoint, array $data): \Generator
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data,
                'stream' => true,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $body = (string) $response->getBody();
                $errorData = json_decode($body, true);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                $errorCode = $errorData['error']['code'] ?? $statusCode;

                throw new ApiException(
                    $errorMessage,
                    $errorCode,
                    null,
                    $errorData['error'] ?? null
                );
            }

            $body = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '') {
                    continue;
                }

                $buffer .= $chunk;

                // Process complete lines (SSE format: "data: {...}\n")
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = rtrim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    // Skip empty lines
                    if ($line === '') {
                        continue;
                    }

                    // Parse SSE format: "data: {...}"
                    if (str_starts_with($line, 'data: ')) {
                        $jsonData = substr($line, 6); // Remove "data: " prefix

                        // Handle [DONE] marker
                        if (trim($jsonData) === '[DONE]') {
                            return;
                        }

                        $decoded = json_decode($jsonData, true);
                        if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                            yield $decoded;
                        }
                    }
                }
            }

            // Process remaining buffer
            if (trim($buffer) !== '') {
                if (str_starts_with($buffer, 'data: ')) {
                    $jsonData = substr($buffer, 6);
                    if (trim($jsonData) !== '[DONE]') {
                        $decoded = json_decode($jsonData, true);
                        if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                            yield $decoded;
                        }
                    }
                }
            }
        } catch (GuzzleException $e) {
            throw new NetworkException(
                'Network error occurred: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Handle API response and check for errors
     *
     * @param ResponseInterface $response
     * @return array<string, mixed>
     * @throws ApiException
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if ($statusCode >= 400) {
            $errorMessage = $data['error']['message'] ?? 'Unknown API error';
            $errorCode = $data['error']['code'] ?? $statusCode;

            throw new ApiException(
                $errorMessage,
                $errorCode,
                null,
                $data['error'] ?? null
            );
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                'Invalid JSON response from API: ' . json_last_error_msg(),
                0
            );
        }

        return $data ?? [];
    }
}

