<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiClient
{
    /** HTTP statuses worth retrying on the next model (overloaded, rate limited, retired). */
    private const RETRYABLE = [404, 429, 500, 502, 503, 504];

    public function isConfigured(): bool
    {
        return filled(config('services.gemini.key')) && config('services.gemini.models') !== [];
    }

    /**
     * Call generateContent and return [model used, decoded response].
     *
     * With $model null the configured models are tried in order. Pass the model that
     * answered earlier in the same exchange to stay on it: thought signatures returned
     * with a function call are only valid for that model.
     *
     * @return array{0: string, 1: array}
     *
     * @throws RuntimeException when no model returns a usable response
     */
    public function generate(array $payload, ?string $model = null): array
    {
        $models = $model !== null ? [$model] : config('services.gemini.models');
        $lastError = 'no model configured';

        foreach ($models as $candidate) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->acceptJson()
                    ->timeout(config('services.gemini.timeout'))
                    ->post(rtrim(config('services.gemini.base_url'), '/')."/models/{$candidate}:generateContent", $payload);
            } catch (ConnectionException $e) {
                $lastError = "{$candidate}: {$e->getMessage()}";
                Log::warning('Gemini request failed', ['model' => $candidate, 'error' => $e->getMessage()]);

                continue;
            }

            if ($response->successful()) {
                return [$candidate, $response->json() ?? []];
            }

            $lastError = "{$candidate}: HTTP {$response->status()} ".($response->json('error.message') ?? '');
            Log::warning('Gemini returned an error', ['model' => $candidate, 'status' => $response->status(), 'error' => $response->json('error.message')]);

            if (! in_array($response->status(), self::RETRYABLE, true)) {
                break; // A bad request or bad key will fail the same way on every model.
            }
        }

        throw new RuntimeException("Gemini unavailable ({$lastError})");
    }
}
