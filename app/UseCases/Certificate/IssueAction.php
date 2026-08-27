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
     * Certificateを発行し、必要に応じてPDFを生成・再生成する。
     *
     * - Certificateなし → 作成 + PDF生成
     * - Certificateあり + 資格変更なし + PDFあり → 何もしない
     * - Certificateあり + 資格変更あり → Certificate更新 + PDF再生成
     * - Certificateあり + PDFなし → PDF生成
     *
     * @throws EnrollmentNotPassedException
     */
    public function __invoke(Enrollment $enrollment): Certificate
    {
        return DB::transaction(function () use ($enrollment) {
            $existing = Certificate::query()
                ->where('enrollment_id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            /*
             * 既存Certificateがある場合
             */
            if ($existing !== null) {
                $certificationChanged =
                    $existing->certification_id !== $enrollment->certification_id;

                $pdfExists =
                    $existing->pdf_path !== null
                    && Storage::disk('local')->exists($existing->pdf_path);

                /*
                 * Enrollmentの資格とCertificateの資格が違う場合
                 * Certificateを現在のEnrollmentに合わせる。
                 */
                if ($certificationChanged) {
                    $existing->forceFill([
                        'certification_id' => $enrollment->certification_id,
                    ])->save();

                    $path = 'certificates/' . Str::ulid() . '.pdf';

                    $this->certificatePdfService->generate(
                        $existing->fresh(),
                        $path
                    );

                    $existing->forceFill([
                        'pdf_path' => $path,
                    ])->save();

                    return $existing->fresh();
                }

                /*
                 * 資格は同じだがPDF実体がない場合
                 */
                if (! $pdfExists) {
                    $path = $existing->pdf_path
                        ?? 'certificates/' . Str::ulid() . '.pdf';

                    $this->certificatePdfService->generate(
                        $existing,
                        $path
                    );

                    $existing->forceFill([
                        'pdf_path' => $path,
                    ])->save();

                    return $existing->fresh();
                }

                /*
                 * Certificateも資格もPDFも正常なら何もしない。
                 */
                return $existing;
            }

            /*
             * Certificateが存在しない場合
             */
            if ($enrollment->status !== EnrollmentStatus::Passed->value) {
                throw new EnrollmentNotPassedException();
            }

            $certificate = Certificate::create([
                'user_id' => $enrollment->user_id,
                'enrollment_id' => $enrollment->id,
                'certification_id' => $enrollment->certification_id,
                'pdf_path' => null,
                'issued_at' => now(),
            ]);

            $path = 'certificates/' . Str::ulid() . '.pdf';

            $this->certificatePdfService->generate(
                $certificate,
                $path
            );

            $certificate->forceFill([
                'pdf_path' => $path,
            ])->save();

            return $certificate->fresh();
        });
    }
}