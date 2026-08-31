<?php

declare(strict_types=1);

namespace App\UseCases\Certificate;

use App\Enums\EnrollmentStatus;
use App\Exceptions\Certification\EnrollmentNotPassedException;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Services\CertificatePdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class IssueAction
{
    public function __construct(
        private CertificatePdfService $certificatePdfService,
    ) {
    }

    /**
     * 修了証を発行する。
     *
     * Certificateが既に存在する場合は、
     * 新しく発行せず、PDF実体がなければ生成する。
     *
     * @throws EnrollmentNotPassedException
     */
    public function __invoke(Enrollment $enrollment): Certificate
    {
        return DB::transaction(function () use ($enrollment) {
            /*
             * 既存Certificateを確認
             */
            $certificate = Certificate::query()
                ->where('enrollment_id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            /*
             * Certificateが既に存在する場合
             *
             * 新規発行はしない。
             * ただしPDF実体がなければ生成する。
             */
            if ($certificate !== null) {
                if (
                    $certificate->pdf_path === null
                    || ! Storage::disk('local')->exists(
                        $certificate->pdf_path
                    )
                ) {
                    $path = $certificate->pdf_path
                        ?? 'certificates/' . Str::ulid() . '.pdf';

                    $certificate->pdf_path = $path;
                    $certificate->save();

                    $this->certificatePdfService->generate(
                        $certificate,
                        $path
                    );
                }

                return $certificate->fresh();
            }

            /*
             * Certificateが存在しない場合のみ、
             * 修了済みであることを確認する。
             */
            $status = $enrollment->status instanceof \BackedEnum
                ? $enrollment->status->value
                : $enrollment->status;

            if (
                $status !== EnrollmentStatus::Passed->value
                || $enrollment->passed_at === null
            ) {
                throw new EnrollmentNotPassedException();
            }

            /*
             * Certificateを新規作成
             */
            $path = 'certificates/' . Str::ulid() . '.pdf';

            $certificate = Certificate::create([
                'user_id' => $enrollment->user_id,
                'enrollment_id' => $enrollment->id,
                'certification_id' => $enrollment->certification_id,
                'pdf_path' => $path,
                'issued_at' => now(),
            ]);

            $this->certificatePdfService->generate(
                $certificate,
                $path
            );

            return $certificate->fresh();
        });
    }
}