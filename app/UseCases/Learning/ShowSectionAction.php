<?php

declare(strict_types=1);

namespace App\UseCases\Learning;

use App\Enums\ContentStatus;
use App\Models\Section;
use App\Models\SectionProgress;
use App\Models\User;
use App\Services\MarkdownRenderingService;
use App\Services\SectionQuestionScoreService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Enums\CertificationStatus;
use App\Enums\EnrollmentStatus;

/**
 * /learning/sections/{section} (5 階層目、Section 詳細) のデータを準備する Action。
 *
 * cascade visibility (Section / Chapter / Part のいずれかが Draft or SoftDelete 済) を 404 で弾き、
 * Markdown 本文を MarkdownRenderingService::toHtml で HTML 化し、読了状態と前後 Section を併せて返す。
 */
final class ShowSectionAction
{
    public function __construct(
        private readonly MarkdownRenderingService $markdown,
        private readonly SectionQuestionScoreService $scoreService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Section $section, User $student): array
    {
        $section->loadMissing('chapter.part.certification');
        $chapter = $section->chapter;
        $part = $chapter?->part;

        if ($section->status !== ContentStatus::Published
            || $chapter === null || $chapter->status !== ContentStatus::Published
            || $part === null || $part->status !== ContentStatus::Published) {
            throw new NotFoundHttpException;
        }

        if ($part->certification?->status !== CertificationStatus::Published) {
            throw new NotFoundHttpException;
    }

        $siblingSections = $chapter->sections()
            ->where('status', ContentStatus::Published->value)
            ->ordered('order')
            ->get();

        $currentIndex = $siblingSections->search(fn ($s) => $s->id === $section->id);
        $prevSection = $currentIndex !== false && $currentIndex > 0
            ? $siblingSections->get($currentIndex - 1)
            : null;
        $nextSection = $currentIndex !== false && $currentIndex < $siblingSections->count() - 1
            ? $siblingSections->get($currentIndex + 1)
            : null;

        $enrollment = $student->enrollments()
            ->where('certification_id', $part->certification_id)
            ->first();

        if ($enrollment === null) {
            abort(403);
        }
        
        if ($part->certification?->status === CertificationStatus::Archived) {
            throw new NotFoundHttpException;
        }

        if ($enrollment !== null
            && $enrollment->status !== EnrollmentStatus::Learning
            && $enrollment->status !== EnrollmentStatus::Passed) {
                bort(403);
            }

        $completed = false;
        if ($enrollment !== null) {
            $completed = SectionProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('section_id', $section->id)
                ->exists();
        }

        $hasSectionQuestions = $section->questions()->exists();
        $sectionQuizSummary = $hasSectionQuestions
            ? $this->scoreService->summarize($student, $section)
            : null;

        return [
            'section' => $section,
            'chapter' => $chapter,
            'part' => $part,
            'enrollment' => $enrollment,
            'bodyHtml' => $this->markdown->toHtml($section->body ?? ''),
            'completed' => $completed,
            'prevSection' => $prevSection,
            'nextSection' => $nextSection,
            'hasSectionQuestions' => $hasSectionQuestions,
            'sectionQuizSummary' => $sectionQuizSummary,
        ];
    }
}

//isugiyama@example.net
//parts/01m0f5n18cmxw4vg8fgdy04f98
//chapters/01m0f5n18h0v2tmacvzj5dv0rg
//sections/01m0f5n18n8wz7j7w55ydrm2xh
