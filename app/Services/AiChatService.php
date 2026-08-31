<?php

namespace App\Services;

use App\Models\Section;
use Illuminate\Support\Facades\Http;

class AiChatService
{
    public function ask(string $message, ?Section $section): string
    {
        $apiKey = config('services.gemini.api_key');

        $prompt = $message;

        $response = Http::retry(
            2,
            100,
            throw: false,
        )->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $apiKey,
            [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
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