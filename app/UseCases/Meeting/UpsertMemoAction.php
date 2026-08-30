<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\MeetingStatusTransitionException;
use App\Models\Meeting;
use App\Models\MeetingMemo;
use App\UseCases\Meeting\UpsertMemoAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsertMemoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_予約済みの面談にメモを登録できる(): void
    {
        $meeting = Meeting::factory()
            ->reserved()
            ->create();

        $action = app(UpsertMemoAction::class);

        $action($meeting, '面談でキャリアについて相談した。');

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => '面談でキャリアについて相談した。',
        ]);
    }

    public function test_完了済みの面談にメモを登録できる(): void
    {
        $meeting = Meeting::factory()
            ->completed()
            ->create();

        $action = app(UpsertMemoAction::class);

        $action($meeting, '面談完了後のメモ。');

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => '面談完了後のメモ。',
        ]);
    }

    public function test_既存のメモがあれば更新される(): void
    {
        $meeting = Meeting::factory()
            ->reserved()
            ->create();

        MeetingMemo::factory()
            ->forMeeting($meeting)
            ->create([
                'body' => '変更前のメモ',
            ]);

        $action = app(UpsertMemoAction::class);

        $action($meeting, '変更後のメモ');

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => '変更後のメモ',
        ]);

        $this->assertDatabaseMissing('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => '変更前のメモ',
        ]);

        $this->assertSame(
            1,
            MeetingMemo::where('meeting_id', $meeting->id)->count(),
        );
    }

    public function test_キャンセル済みの面談にはメモを登録できない(): void
    {
        $meeting = Meeting::factory()
            ->canceled()
            ->create();

        $action = app(UpsertMemoAction::class);

        $this->expectException(MeetingStatusTransitionException::class);

        $action($meeting, '登録できないメモ');
    }
}

