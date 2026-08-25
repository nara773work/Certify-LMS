<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiChatService
{
    public function ask(string $message): string
    {
        $apiKey = config('services.gemini.api_key');

        $response = Http::post(
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $apiKey,
    [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $message,
                    ],
                ],
            ],
        ],
    ]
);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Gemini API error: ' . $response->body()
            );
        }

        return $response->json(
            'candidates.0.content.parts.0.text',
            ''
        );
    }
}