<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Learning;

use App\Enums\ContentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Certification;
use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\SectionProgress;
use App\Models\User;
use App\Services\Learning\ProgressSummary;
use App\Services\Learning\ProgressSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProgressSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProgressSummaryService();
    }

    public function test_progress_is_zero_when_nothing_is_completed(): void
    {
        $enrollment = $this->createEnrollment();

        $part = $this->createPart($enrollment);
        $chapter = $this->createChapter($part);

        $this->createSection($chapter);
        $this->createSection($chapter);

        $summary = $this->service->summarize($enrollment);

        $this->assertInstanceOf(ProgressSummary::class, $summary);

        $this->assertSame(2, $summary->sectionsTotal);
        $this->assertSame(0, $summary->sectionsCompleted);
        $this->assertSame(0.0, $summary->sectionCompletionRatio);

        $this->assertSame(1, $summary->chaptersTotal);
        $this->assertSame(0, $summary->chaptersCompleted);
        $this->assertSame(0.0, $summary->chapterCompletionRatio);

        $this->assertSame(1, $summary->partsTotal);
        $this->assertSame(0, $summary->partsCompleted);
        $this->assertSame(0.0, $summary->partCompletionRatio);

        $this->assertSame(0.0, $summary->overallCompletionRatio);
    }

    public function test_section_completion_is_counted(): void
    {
        $enrollment = $this->createEnrollment();

        $part = $this->createPart($enrollment);
        $chapter = $this->createChapter($part);

        $section1 = $this->createSection($chapter);
        $this->createSection($chapter);

        $this->createProgress($enrollment, $section1);

        $summary = $this->service->summarize($enrollment);

        $this->assertSame(2, $summary->sectionsTotal);
        $this->assertSame(1, $summary->sectionsCompleted);
        $this->assertSame(0.5, $summary->sectionCompletionRatio);

        $this->assertSame(0, $summary->chaptersCompleted);
        $this->assertSame(0.0, $summary->chapterCompletionRatio);

        $this->assertSame(0, $summary->partsCompleted);
        $this->assertSame(0.0, $summary->partCompletionRatio);
    }

    public function test_chapter_is_completed_when_all_sections_are_completed(): void
    {
        $enrollment = $this->createEnrollment();

        $part = $this->createPart($enrollment);
        $chapter = $this->createChapter($part);

        $section1 = $this->createSection($chapter);
        $section2 = $this->createSection($chapter);

        $this->createProgress($enrollment, $section1);
        $this->createProgress($enrollment, $section2);

        $summary = $this->service->summarize($enrollment);

        $this->assertSame(2, $summary->sectionsTotal);
        $this->assertSame(2, $summary->sectionsCompleted);
        $this->assertSame(1.0, $summary->sectionCompletionRatio);

        $this->assertSame(1, $summary->chaptersTotal);
        $this->assertSame(1, $summary->chaptersCompleted);
        $this->assertSame(1.0, $summary->chapterCompletionRatio);

        $this->assertSame(1, $summary->partsTotal);
        $this->assertSame(1, $summary->partsCompleted);
        $this->assertSame(1.0, $summary->partCompletionRatio);

        $this->assertSame(1.0, $summary->overallCompletionRatio);
    }

    public function test_part_is_completed_when_all_chapters_are_completed(): void
    {
        $enrollment = $this->createEnrollment();

        $part = $this->createPart($enrollment);

        $chapter1 = $this->createChapter($part);
        $chapter2 = $this->createChapter($part);

        $section1 = $this->createSection($chapter1);
        $section2 = $this->createSection($chapter2);

        $this->createProgress($enrollment, $section1);
        $this->createProgress($enrollment, $section2);

        $summary = $this->service->summarize($enrollment);

        $this->assertSame(2, $summary->sectionsTotal);
        $this->assertSame(2, $summary->sectionsCompleted);

        $this->assertSame(2, $summary->chaptersTotal);
        $this->assertSame(2, $summary->chaptersCompleted);
        $this->assertSame(1.0, $summary->chapterCompletionRatio);

        $this->assertSame(1, $summary->partsTotal);
        $this->assertSame(1, $summary->partsCompleted);
        $this->assertSame(1.0, $summary->partCompletionRatio);
    }

    public function test_draft_content_is_not_included_in_progress(): void
    {
        $enrollment = $this->createEnrollment();

        $part = $this->createPart($enrollment);

        $publishedChapter = $this->createChapter(
            $part,
            ContentStatus::Published->value
        );

        $draftChapter = $this->createChapter(
            $part,
            ContentStatus::Draft->value
        );

        $publishedSection = $this->createSection(
            $publishedChapter,
            ContentStatus::Published->value
        );

        $draftSection = $this->createSection(
            $publishedChapter,
            ContentStatus::Draft->value
        );

        $this->createSection(
            $draftChapter,
            ContentStatus::Published->value
        );

        $this->createProgress($enrollment, $publishedSection);
        $this->createProgress($enrollment, $draftSection);

        $summary = $this->service->summarize($enrollment);

        $this->assertSame(1, $summary->sectionsTotal);
        $this->assertSame(1, $summary->sectionsCompleted);

        $this->assertSame(1, $summary->chaptersTotal);
        $this->assertSame(1, $summary->chaptersCompleted);

        $this->assertSame(1, $summary->partsTotal);
        $this->assertSame(1, $summary->partsCompleted);
    }

    public function test_empty_content_returns_zero_ratios(): void
    {
        $enrollment = $this->createEnrollment();

        $this->createPart($enrollment);

        $summary = $this->service->summarize($enrollment);

        $this->assertSame(0, $summary->sectionsTotal);
        $this->assertSame(0, $summary->sectionsCompleted);
        $this->assertSame(0.0, $summary->sectionCompletionRatio);

        $this->assertSame(0, $summary->chaptersTotal);
        $this->assertSame(0, $summary->chaptersCompleted);
        $this->assertSame(0.0, $summary->chapterCompletionRatio);

        $this->assertSame(1, $summary->partsTotal);
        $this->assertSame(0, $summary->partsCompleted);
        $this->assertSame(0.0, $summary->partCompletionRatio);

        $this->assertSame(0.0, $summary->overallCompletionRatio);
    }

    private function createEnrollment(): Enrollment
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()
            ->published()
            ->create();

        return Enrollment::factory()
            ->for($student)
            ->for($certification)
            ->state([
                'status' => EnrollmentStatus::Learning,
            ])
            ->create();
    }

    private function createPart(
        Enrollment $enrollment,
        string $status = 'published',
    ): Part {
        return Part::create([
            'certification_id' => $enrollment->certification_id,
            'title' => 'Test Part',
            'description' => 'Test Part Description',
            'status' => $status,
            'order' => 1,
        ]);
    }

    private function createChapter(
        Part $part,
        string $status = 'published',
    ): Chapter {
        return Chapter::create([
            'part_id' => $part->id,
            'title' => 'Test Chapter',
            'description' => 'Test Chapter Description',
            'status' => $status,
            'order' => 1,
        ]);
    }

    private function createSection(
        Chapter $chapter,
        string $status = 'published',
    ): Section {
        return Section::create([
            'chapter_id' => $chapter->id,
            'title' => 'Test Section',
            'body' => 'Test Section Body',
            'status' => $status,
            'order' => 1,
        ]);
    }

private function createProgress(
    Enrollment $enrollment,
    Section $section,
): SectionProgress {
    return SectionProgress::create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
        'completed_at' => now(),
    ]);
}
}