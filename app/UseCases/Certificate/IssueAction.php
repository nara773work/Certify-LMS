<?php

declare(strict_types=1);

namespace App\UseCases\Certificate;

use App\Enums\EnrollmentStatus;
use App\Exceptions\Certification\CertificateAlreadyIssuedException;
use App\Exceptions\Certification\EnrollmentNotPassedException;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Services\CertificatePdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IssueAction
{
    public function __construct(
        private CertificatePdfService $certificatePdfService,
    ) {
    }

    /**
     * @throws EnrollmentNotPassedException
     * @throws CertificateAlreadyIssuedException
     */
    public function __invoke(Enrollment $enrollment): Certificate
    {
        if (
            $enrollment->status !== EnrollmentStatus::Passed ||
            $enrollment->passed_at === null
        ) {
            throw new EnrollmentNotPassedException;
        }

        $certificate = DB::transaction(function () use ($enrollment) {
            $existing = Certificate::query()
                ->where('enrollment_id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new CertificateAlreadyIssuedException;
            }

            return Certificate::create([
                'user_id' => $enrollment->user_id,
                'enrollment_id' => $enrollment->id,
                'certification_id' => $enrollment->certification_id,
                'pdf_path' => 'certificates/' . Str::ulid() . '.pdf',
                'issued_at' => now(),
            ]);
        });

        // 修了証PDFを生成して保存
        $this->certificatePdfService->generate(
            $certificate,
            $certificate->pdf_path
        );

        return $certificate;
    }
}