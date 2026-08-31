<?php

declare(strict_types=1);

namespace App\UseCases\Learning;

use App\Enums\ContentStatus;
use App\Models\Part;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Enums\CertificationStatus;
use App\Services\Learning\ProgressSummaryService;

/**
 * /learning/parts/{part} (3 階層目、Chapter 一覧) のデータを準備する Action。
 *
 * 公開済 Chapter 一覧 + Part の Published 確認 (非公開なら 404) に加え、
 * 各 Chapter の Section 総数 / 読了済 Section 数 を 1 ショット SQL で集計して Blade に渡す
 * (Chapter 完了バッジの表示用)。受講生が当該資格に未登録の場合は完了数 0 として扱う。
 */
final class ShowPartAction
{
    public function __construct(
        private readonly ProgressSummaryService $progressSummaryService,
    ) {}
    /**
     * @return array<string, mixed>
     */
    
    public function __invoke(Part $part, User $student): array
    {
        $part->loadMissing('certification');

        if ($part->certification?->status === CertificationStatus::Archived) {
            throw new NotFoundHttpException;
        }

        if ($part->status !== ContentStatus::Published) {
            throw new NotFoundHttpException;
        }

        $enrollment = $student->enrollments()
            ->where('certification_id', $part->certification_id)
            ->first();
        
        if ($enrollment === null) {
            abort(403);
        }


        $chapters = $part->chapters()
            ->where('status', ContentStatus::Published->value)
            ->ordered('order')
            ->withCount([
                'sections as sections_total_count' => fn ($q) => $q
                    ->where('status', ContentStatus::Published->value),
            ])
            ->get();
      
        $completedByChapter =
            $this->progressSummaryService
                ->completedSectionsByChapter($enrollment, $chapters);

        return [
    'part' => $part->load('certification'),
    'chapters' => $chapters,
    'completedByChapter' => $completedByChapter,
];
    }
}
