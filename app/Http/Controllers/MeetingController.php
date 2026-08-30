<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\MeetingStatus;
use App\Exceptions\MeetingQuota\InsufficientMeetingQuotaException;
use App\Exceptions\Mentoring\MeetingAlreadyStartedException;
use App\Exceptions\Mentoring\MeetingNoAvailableCoachException;
use App\Exceptions\Mentoring\MeetingStatusTransitionException;
use App\Http\Requests\Meeting\AvailabilityRequest;
use App\Http\Requests\Meeting\IndexAsCoachRequest;
use App\Http\Requests\Meeting\IndexRequest;
use App\Http\Requests\Meeting\StoreRequest;
use App\Http\Requests\Meeting\UpsertMemoRequest;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\MeetingMemo;
use App\Models\User;
use App\Services\MeetingAvailabilityService;
use App\Services\MeetingQuotaService;
use App\Services\CoachMeetingLoadService;
use App\UseCases\MeetingQuota\ConsumeQuotaAction;
use App\UseCases\MeetingQuota\RefundQuotaAction;
use Illuminate\Support\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Notifications\MeetingReservationNotification;
use App\UseCases\Meeting\IndexAction;
use App\UseCases\Meeting\IndexAsCoachAction;
use App\UseCases\Meeting\ShowAction;
use App\UseCases\Meeting\CreateAction;
use App\UseCases\Meeting\StoreAction;
use App\UseCases\Meeting\CreateFallbackAction;
use App\UseCases\Meeting\CancelAction;
use App\UseCases\Meeting\UpsertMemoAction;
use App\UseCases\Meeting\FindAvailableAction;
/**
 * 1on1 面談予約 (Meeting) の HTTP エントリポイント。
 *
 * 受講生視点(index / show / create / store / cancel / fetchAvailability)とコーチ視点
 * (indexAsCoach / upsertMemo)を 1 Controller に集約する。予約 / キャンセル / メモ保存の
 * 状態変更系は残面談回数の消費・返却、通知発火、トランザクション境界を method 内で扱い、
 * 取得系はクエリ組み立てを method 内で行う。認可は $this->authorize() または FormRequest::authorize()。
 */
class MeetingController extends Controller
{
    /**
     * 受講生本人の面談一覧。filter (upcoming/past/all) クエリで履歴を切り替える。
     */

    public function index(IndexRequest $request, MeetingQuotaService $meetingQuota,IndexAction $action): View
    {
        $filter = $request->validated('filter') ?? 'upcoming';

        $meetings = $action($request->user(), $filter);

        return view('meeting.index', [
            'meetings' => $meetings,
            'filter' => $filter,
            'meetingsRemaining' => $meetingQuota->remaining($request->user()),
        ]);
    }

    /**
     * コーチ宛の面談一覧。担当受講生 / 受講登録での絞り込みを併せて提供する。
     */
    public function indexAsCoach(IndexAsCoachRequest $request,indexAsCoachAction $action): View
    {
        $filters = $request->validated();
        $filter = $filters['filter'] ?? 'upcoming';
        $studentId = $filters['student'] ?? null;
        $enrollmentId = $filters['enrollment'] ?? null;

        $meetings = $action($request->user(), $filter,$studentId,$enrollmentId);

        return view('meeting.coach.index', [
            'meetings' => $meetings,
            'filter' => $filter,
            'studentFilter' => $studentId,
            'enrollmentFilter' => $enrollmentId,
        ]);
    }

    /**
     * 面談詳細(当事者共通)。Policy で coach/student の閲覧範囲を絞る。
     */
    public function show(Meeting $meeting,ShowAction $action): View {
    $this->authorize('view', $meeting);

    $meeting = $action($meeting);

    return view('meeting.show', [
        'meeting' => $meeting,
    ]);
}

    /**
     * 予約画面(受講生): URL に Enrollment を含む正規ルートで表示する。
     */
    public function create(Enrollment $enrollment, MeetingQuotaService $meetingQuota,CreateAction $action): View
    {
        $this->authorize('create', Meeting::class);

        abort_unless($enrollment->user_id === auth()->id(), 403);
        abort_unless($enrollment->status === EnrollmentStatus::Learning, 403);

        $enrollment = $action($enrollment);

        return view('meeting.create', [
            'enrollment' => $enrollment,
            'meetingsRemaining' => $meetingQuota->remaining(auth()->user()),
        ]);
    }

    /**
     * 予約画面のエントリポイント(URL に Enrollment 無し)。
     * `resolve-default-enrollment` Middleware が default 資格に redirect するため、
     * 本 method に到達するのは default 未設定 + 残存 Enrollment が 0 件 or 2+ 件のケース。
     */
    public function createFallback(CreateFallbackAction $action): View
    {
        $user = auth()->user();
        
        $enrollments = $action($user);

        return view('meeting.empty-state', [
            'enrollments' => $enrollments ?? collect(),
        ]);
    }

    /**
     * 受講生の予約申請。残面談回数を確認し、空き枠から過去実績最少のコーチを自動割当して reserved で確定する。
     * 同時刻 race condition は (coach_id, scheduled_at) UNIQUE 違反として検知し 409 へ変換する。
     */
    public function store(
    Enrollment $enrollment,
    StoreRequest $request,
    StoreAction $action,
): RedirectResponse {
    $meeting = $action(
        $enrollment,
        $request->user(),
        $request->validated(),
    );
    return redirect()
        ->route('meetings.show', $meeting)
        ->with('success', '面談を予約しました。');
}


    /**
     * 当事者(受講生 or コーチ)による面談キャンセル。
     * reserved かつ開始前のみキャンセル可。消費済の面談回数 1 回分を返却する。
     */
    public function cancel(Meeting $meeting,CancelAction $action): RedirectResponse {
    $this->authorize('cancel', $meeting);

    $actor = auth()->user();

    $action(
        $meeting,
        $actor
    );

    return redirect()
        ->route('meetings.show', $meeting)
        ->with('success', '面談をキャンセルしました。面談回数を返却しました。');
}

    /**
     * 担当コーチによる面談メモ作成・更新。canceled の面談にはメモを残せない。
     */

    public function upsertMemo(Meeting $meeting,UpsertMemoRequest $request,UpsertMemoAction $action): RedirectResponse {
    $body = $request->validated('body');

    $this->authorize('upsertMemo', $meeting);

    $action($meeting, $body);

    return redirect()
        ->route('meetings.show', $meeting)
        ->with('success', '面談メモを保存しました。');
}

    /**
     * 予約画面が呼ぶ空き枠取得 JSON エンドポイント。
     */
    public function fetchAvailability(Enrollment $enrollment,AvailabilityRequest $request,FindAvailableAction $action): JsonResponse {
    $slots = $action(
        $enrollment,
        $request->validated('date')
    );

    return response()->json([
        'date' => Carbon::parse(
            $request->validated('date')
        )->toDateString(),

        'slots' => $slots->map(fn (array $slot) => [
            'slot_start' => $slot['slot_start']->toIso8601String(),
            'slot_end' => $slot['slot_end']->toIso8601String(),
            'available_coach_count' => $slot['available_coach_count'],
        ])->all(),
    ]);
}
}
