<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\TermType;
use App\Models\Certificate;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DownloadSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * ユーザー
         */
        $admin = User::where(
            'email',
            'admin@certify-lms.test'
        )->firstOrFail();

        $student1 = User::where(
            'email',
            'student-graduated@certify-lms.test'
        )->firstOrFail();

        $student2 = User::where(
            'email',
            'student-graduated2@certify-lms.test'
        )->firstOrFail();

        /*
         * 資格A
         *
         * 花子 → 日商簿記 2 級
         *
         * 担当コーチは CertificationSeeder で設定する。
         */
        $certification1 = Certification::query()
            ->where(
                'status',
                CertificationStatus::Published->value
            )
            ->where('name', '日商簿記 2 級')
            ->first();

        if ($certification1 === null) {
            $this->command?->error(
                'DownloadSeeder: 公開済みの「日商簿記 2 級」が見つかりません。'
            );

            return;
        }

        /*
         * 資格B
         *
         * 一郎 → 基本情報技術者試験
         *
         * 担当コーチは CertificationSeeder で設定する。
         */
        $certification2 = Certification::query()
            ->where(
                'status',
                CertificationStatus::Published->value
            )
            ->where('name', '基本情報技術者試験')
            ->first();

        if ($certification2 === null) {
            $this->command?->error(
                'DownloadSeeder: 公開済みの「基本情報技術者試験」が見つかりません。'
            );

            return;
        }

        /*
         * Enrollment
         *
         * 花子 → 日商簿記 2 級
         * 一郎 → 基本情報技術者試験
         */
        $enrollment1 = $this->createPassedEnrollment(
            $student2,
            $certification1
        );

        $enrollment2 = $this->createPassedEnrollment(
            $student1,
            $certification2
        );

        /*
         * Certificate + PDF
         *
         * Certificateがなければ作成。
         * Certificateがあれば再利用。
         * PDF実体がなければ生成。
         */
        $certificate1 = $this->createCertificateWithPdf(
            $enrollment1
        );

        $certificate2 = $this->createCertificateWithPdf(
            $enrollment2
        );

        $this->assertPdfExists(
            $certificate1->pdf_path,
            '花子'
        );

        $this->assertPdfExists(
            $certificate2->pdf_path,
            '一郎'
        );

        $this->command?->info(
            'DownloadSeeder: ダウンロード確認用の修了証2件を投入しました。'
        );

        $this->command?->info(
            '花子 → ' . $certification1->name
        );

        $this->command?->info(
            '一郎 → ' . $certification2->name
        );

        $this->command?->info(
            '担当コーチは CertificationSeeder の設定を使用します。'
        );

        $this->command?->info(
            'PDF実体: OK'
        );
    }

    /**
     * 修了済みEnrollmentを作成・更新する。
     */
    private function createPassedEnrollment(
        User $student,
        Certification $certification,
    ): Enrollment {
        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('certification_id', $certification->id)
            ->first();

        $startedAt = now()->subDays(90);
        $passedAt = now()->subDays(7);

        if ($enrollment === null) {
            $enrollment = new Enrollment();

            $enrollment->user_id = $student->id;
            $enrollment->certification_id = $certification->id;
            $enrollment->created_at = $startedAt;
        }

        $enrollment->exam_date = $passedAt->toDateString();
        $enrollment->status = EnrollmentStatus::Passed->value;
        $enrollment->current_term = TermType::MockPractice->value;
        $enrollment->passed_at = $passedAt;
        $enrollment->updated_at = $passedAt;

        $enrollment->save();

        return $enrollment;
    }

    /**
     * Certificateを作成し、PDF実体を生成する。
     */
    private function createCertificateWithPdf(
        Enrollment $enrollment,
    ): Certificate {
        $certificate = Certificate::query()
            ->where('enrollment_id', $enrollment->id)
            ->first();

        if ($certificate === null) {
            $certificate = new Certificate();

            $certificate->id = (string) Str::ulid();
            $certificate->user_id = $enrollment->user_id;
            $certificate->enrollment_id = $enrollment->id;
            $certificate->certification_id = $enrollment->certification_id;
            $certificate->issued_at = now();
            $certificate->pdf_path =
                'certificates/' . $certificate->id . '.pdf';

            $certificate->save();
        }

        if (
            $certificate->pdf_path === null
            || ! Storage::disk('local')->exists(
                $certificate->pdf_path
            )
        ) {
            app(\App\Services\CertificatePdfService::class)
                ->generate(
                    $certificate,
                    $certificate->pdf_path
                );
        }

        $certificate->refresh();

        if (
            $certificate->pdf_path === null
            || ! Storage::disk('local')->exists(
                $certificate->pdf_path
            )
        ) {
            throw new \RuntimeException(
                '修了証PDFの生成に失敗しました。'
                . ' enrollment_id=' . $enrollment->id
            );
        }

        return $certificate;
    }

    /**
     * PDFの実体が存在することを確認する。
     */
    private function assertPdfExists(
        ?string $pdfPath,
        string $studentName,
    ): void {
        if (
            $pdfPath === null
            || ! Storage::disk('local')->exists($pdfPath)
        ) {
            throw new \RuntimeException(
                $studentName . 'の修了証PDFが実体化されていません。'
                . ' pdf_path=' . ($pdfPath ?? 'null')
            );
        }
    }
}
