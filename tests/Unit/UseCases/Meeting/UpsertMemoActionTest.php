<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\Mentoring\MeetingStatusTransitionException;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\MeetingMemo;
use App\Models\User;
use App\UseCases\Meeting\UpsertMemoAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsertMemoActionTest extends TestCase
{
    use RefreshDatabase;

    private function createMeeting(): Meeting
    {
        $student = User::create([
            'name' => 'テスト受講生',
            'email' => 'student-memo-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'status' => 'in_progress',
            'profile_setup_completed' => true,
            'email_verified_at' => now(),
        ]);

        $coach = User::create([
            'name' => 'テストコーチ',
            'email' => 'coach-memo-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'status' => 'in_progress',
            'profile_setup_completed' => true,
            'email_verified_at' => now(),
        ]);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
        ]);

        return Meeting::create([
            'enrollment_id' => $enrollment->id,
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addDay(),
            'status' => MeetingStatus::Reserved->value,
            'topic' => 'テスト面談',
        ]);
    }

    /**
     * 面談メモを新規登録できる。
     */
    public function test_面談メモを新規登録できる(): void
    {
        $meeting = $this->createMeeting();

        $action = new UpsertMemoAction;

        $memo = $action($meeting, 'テストメモ');

        $this->assertInstanceOf(MeetingMemo::class, $memo);

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => 'テストメモ',
        ]);
    }

    /**
     * 既存の面談メモがあれば更新する。
     */
    public function test_既存の面談メモを更新できる(): void
    {
        $meeting = $this->createMeeting();

        MeetingMemo::create([
            'meeting_id' => $meeting->id,
            'body' => '古いメモ',
        ]);

        $action = new UpsertMemoAction;

        $memo = $action($meeting, '新しいメモ');

        $this->assertSame('新しいメモ', $memo->body);

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => '新しいメモ',
        ]);

        $this->assertDatabaseCount('meeting_memos', 1);
    }

    /**
     * キャンセル済みの面談にはメモを登録できない。
     */
    public function test_キャンセル済みの面談にはメモを登録できない(): void
    {
        $meeting = $this->createMeeting();

        $meeting->update([
            'status' => MeetingStatus::Canceled->value,
        ]);

        $meeting->refresh();

        $action = new UpsertMemoAction;

        $this->expectException(MeetingStatusTransitionException::class);

        $action($meeting, 'テストメモ');
    }
}
