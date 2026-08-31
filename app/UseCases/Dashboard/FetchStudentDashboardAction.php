<?php

declare(strict_types=1);

namespace App\UseCases\Dashboard;

use App\Enums\CertificationStatus;
use App\Enums\ContentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\MeetingStatus;
use App\Http\Controllers\DashboardController;
use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\Meeting;
use App\Models\MeetingPack;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use App\Services\Contracts\WeaknessAnalysisServiceContract;
use App\Services\LearningCalendarService;
use App\Services\LearningHourTargetService;
use App\Services\MeetingQuotaService;
use App\Services\PlanExpirationService;
use App\Services\StreakService;
use App\UseCases\Dashboard\ViewModels\PlanInfoPanel;
use App\UseCases\Dashboard\ViewModels\ResumeCard;
use App\UseCases\Dashboard\ViewModels\StudentDashboardViewModel;
use App\UseCases\Dashboard\ViewModels\StudentEnrollmentCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Services\Learning\ProgressSummaryService;

/**
 * 受講生ダッシュボードの ViewModel を組み立てる Action。
 *
 * プラン情報パネル(残面談 + 残日数 + 追加面談購入 CTA) + 前回の続き + 受講中の資格カード(learning) +
 * 修了済資格セクション + ストリーク + 学習カレンダー + 個人目標タイムライン + 今後の面談予定 を集約する。
 *
 * - 受講中の資格カードは学習中のみ。修了済は別セクションのリストに集約する
 * - 受講中の資格カードの進捗集計は 1 クエリで一括算出して N+1 回避
 * - 各セクション build は `safe()` で包み、Service 例外で画面全体が 500 化するのを防ぐ
 *
 * @see DashboardController::index()
 */
final class FetchStudentDashboardAction
{
    use HasDashboardSafeFetch;

    public function __construct(
        private readonly StreakService $streak,
        private readonly LearningCalendarService $learningCalendar,
        private readonly LearningHourTargetService $hourTarget,
        private readonly WeaknessAnalysisServiceContract $weakness,
        private readonly CompletionEligibilityService $completion,
        private readonly MeetingQuotaService $meetingQuota,
        private readonly PlanExpirationService $planExpiration,
        private readonly ProgressSummaryService $progressSummaryService,
    ) {}

    public function __invoke(User $student): StudentDashboardViewModel
    {
        $learningEnrollments = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('status', EnrollmentStatus::Learning)
            ->with('certification')
            ->get();

        $passedEnrollments = $this->buildPassedEnrollments($student);

        return new StudentDashboardViewModel(
            planInfo: $this->safe(fn () => $this->buildPlanInfo($student)),
            resume: $this->safe(fn () => $this->buildResumeCard($student)),
            enrollmentCards: $this->buildEnrollmentCards($learningEnrollments),
            passedEnrollments: $passedEnrollments,
            streak: $this->safe(fn () => $this->streak->calculate($student)),
            learningCalendar: $this->safe(fn () => $this->learningCalendar->build($student)),
            goalTimeline: $this->safe(fn () => $this->buildGoalTimeline($student)),
            upcomingMeetings: $this->safe(fn () => $this->buildUpcomingMeetings($student)),
            // 学習中・修了済のどちらも無いときだけ「未受講」とみなす(全資格修了済の受講生は修了済セクションを見せる)
            hasNoEnrollment: $learningEnrollments->isEmpty() && $passedEnrollments->isEmpty(),
        );
    }

    private function buildPlanInfo(User $student): PlanInfoPanel
    {
        return new PlanInfoPanel(
            planName: $student->plan?->name,
            courseDaysRemaining: $this->planExpiration->daysRemaining($student),
            meetingsRemaining: $this->meetingQuota->remaining($student),
            meetingPacks: MeetingPack::query()
                ->published()
                ->ordered()
                ->get(),
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Enrollment> $learningEnrollments
     *
     * @return Collection<int, StudentEnrollmentCard>
     */
    private function buildEnrollmentCards($learningEnrollments): Collection
{
    $progressMap = $this->safe(
        fn () => $this->progressSummaryService
            ->batchSummarize($learningEnrollments)
    ) ?? [];

    return $learningEnrollments
        ->map(fn (Enrollment $enrollment) =>
            $this->buildCard(
                $enrollment,
                $progressMap[$enrollment->id] ?? null
            )
        )
        ->values();
}

    

    /**
     * 同資格の公開 Section を Part → Chapter → Section の表示順に並べ、指定 Section 以降で最初の未読を返す。
     * 後方に未読が無ければ全体で最初の未読(前半の取りこぼし)を返し、すべて読了済なら null を返す。
     */
    private function findNextUnreadSection(string $certificationId, string $enrollmentId, string $afterSectionId): ?\stdClass
    {
        $sections = DB::table('sections as s')
            ->join('chapters as c', 'c.id', '=', 's.chapter_id')
            ->join('parts as p', 'p.id', '=', 'c.part_id')
            ->leftJoin('section_progresses as sp', function ($join) use ($enrollmentId): void {
                $join->on('sp.section_id', '=', 's.id')
                    ->where('sp.enrollment_id', '=', $enrollmentId);
            })
            ->where('p.certification_id', $certificationId)
            ->where('s.status', ContentStatus::Published->value)
            ->where('c.status', ContentStatus::Published->value)
            ->where('p.status', ContentStatus::Published->value)
            ->orderBy('p.order')
            ->orderBy('c.order')
            ->orderBy('s.order')
            ->select([
                's.id as section_id',
                's.title as section_title',
                'c.title as chapter_title',
                'p.title as part_title',
                'sp.id as progress_id',
            ])
            ->get();

        $afterIndex = $sections->search(fn (\stdClass $row): bool => $row->section_id === $afterSectionId);
        $candidates = $afterIndex === false ? $sections : $sections->slice($afterIndex + 1);

        return $candidates->first(fn (\stdClass $row): bool => $row->progress_id === null)
            ?? $sections->first(fn (\stdClass $row): bool => $row->progress_id === null);
    }

    /**
     * 受講生が当事者の個人目標タイムライン。未達成を先頭にし、その中で目標期日が近い順
     * (期日未設定は末尾)、同条件は新しく作成した順に並べる。
     *
     * @return Collection<int, EnrollmentGoal>
     */
    private function buildGoalTimeline(User $student): Collection
    {
        // 個人目標ルートが未登録の環境では空タイムラインを返す（機能未提供時の防御）
        if (! Route::has('enrollments.goals.store')) {
            return collect();
        }

        return EnrollmentGoal::query()
            ->whereHas('enrollment', fn ($q) => $q->where('user_id', $student->id))
            ->with('enrollment.certification')
            ->displayOrder()
            ->get()
            ->values();
    }

    /**
     * 受講生が当事者の今後の面談予定。今日 0:00 以降の reserved 最大 5 件を昇順に並べる。
     *
     * @return Collection<int, Meeting>
     */
    private function buildUpcomingMeetings(User $student): Collection
    {
        return Meeting::query()
            ->where('student_id', $student->id)
            ->where('status', MeetingStatus::Reserved)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->with(['coach', 'enrollment.certification'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->values();
    }
}
