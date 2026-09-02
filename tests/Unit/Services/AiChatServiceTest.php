<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AiChatService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @group external-api
 */
final class AiChatServiceTest extends TestCase
{
    private AiChatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AiChatService;
    }

    /**
     * Geminiから正常な回答が返された場合、
     * その回答を返す。
     */
    public function test正常な応答を返す(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'これはGeminiからの回答です。',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->ask(
            'Laravelについて教えてください。',
            null,
        );

        $this->assertSame(
            'これはGeminiからの回答です。',
            $result
        );

        Http::assertSentCount(1);
    }

    /**
     * Gemini APIがエラーを返した場合、
     * RuntimeExceptionを投げる。
     */
    public function test_gemini_ap_iエラーの場合は例外を投げる(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'message' => 'API error',
                ],
            ], 500),
        ]);

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Gemini API error'
        );

        $this->service->ask(
            '質問です。',
            null,
        );
    }

    /**
     * Geminiから回答本文が返されなかった場合、
     * 空文字を返す。
     */
    public function test空応答の場合は空文字を返す(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [],
            ], 200),
        ]);

        $result = $this->service->ask(
            '質問です。',
            null,
        );

        $this->assertSame('', $result);

        Http::assertSentCount(1);
    }

    /**
     * 一時的なAPIエラーが発生した場合、
     * 再試行して成功すれば回答を返す。
     */
    public function test一時的なエラー後に再試行して成功する(): void
    {
        Http::fakeSequence()
            ->push([
                'error' => [
                    'message' => 'Temporary error',
                ],
            ], 500)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '再試行後の回答です。',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200);

        $result = $this->service->ask(
            '再試行テストです。',
            null,
        );

        $this->assertSame(
            '再試行後の回答です。',
            $result
        );

        Http::assertSentCount(2);
    }

    /**
     * Gemini APIに送信するリクエストが、
     * 想定したプロンプト構造になっている。
     */
    public function test_geminiへの送信内容が正しい(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '回答',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $message = 'LaravelのServiceについて教えてください。';

        $this->service->ask(
            $message,
            null,
        );

        Http::assertSent(function ($request) use ($message) {
            $data = $request->data();

            return $request->method() === 'POST'
                && isset($data['contents'])
                && count($data['contents']) === 1
                && isset($data['contents'][0]['parts'])
                && count($data['contents'][0]['parts']) === 1
                && $data['contents'][0]['parts'][0]['text'] === $message;
        });
    }
}
