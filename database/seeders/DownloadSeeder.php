<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\TermType;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Certificate;

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

        $coach1 = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $coach2 = User::where(
            'email',
            'coach2@certify-lms.test'
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
         * 一郎 → 日商簿記 2 級
         * コーチ1 → 担当
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
         * 花子 → TOEICを優先。
         * TOEICがなければ簿記以外の公開済み資格を使用。
         * コーチ2 → 担当
         */
        $certification2 = Certification::query()
            ->where(
                'status',
                CertificationStatus::Published->value
            )
            ->where('name', 'TOEIC')
            ->where('id', '!=', $certification1->id)
            ->first();

        if ($certification2 === null) {
            $certification2 = Certification::query()
                ->where(
                    'status',
                    CertificationStatus::Published->value
                )
                ->where('id', '!=', $certification1->id)
                ->orderBy('created_at')
                ->first();
        }

        if ($certification2 === null) {
            $this->command?->error(
                'DownloadSeeder: 2件目の公開済み資格が見つかりません。'
            );

            return;
        }

        /*
         * 担当コーチを設定
         *
         * 資格A → コーチ1
         * 資格B → コーチ2
         */
        $this->assignCoach(
            $coach1,
            $certification1,
            $admin
        );

        $this->assignCoach(
            $coach2,
            $certification2,
            $admin
        );

        /*
         * 以前のSeeder実行などで逆の担当が残っている場合、
         * ダウンロード確認用データとして担当外になるよう解除する。
         *
         * コーチ1 → 資格Bは担当外
         * コーチ2 → 資格Aは担当外
         */
        $this->unassignCoach(
            $coach1,
            $certification2
        );

        $this->unassignCoach(
            $coach2,
            $certification1
        );

        /*
         * Enrollment
         *
         * 一郎 → 資格A
         * 花子 → 資格B
         */
        $enrollment1 = $this->createPassedEnrollment(
            $student1,
            $certification1
        );

        $enrollment2 = $this->createPassedEnrollment(
            $student2,
            $certification2
        );

        /*
         * Certificate + PDF
         *
         * Certificateがなければ作成。
         * Certificateがあれば再利用。
         * 資格が変わっていれば更新。
         * PDF実体がなければ生成。
         */
        $certificate1 = $this->createCertificateWithPdf( $enrollment1 );
        $certificate2 = $this->createCertificateWithPdf( $enrollment2 );
        $this->assertPdfExists( $certificate1->pdf_path, '一郎' );
        $this->assertPdfExists( $certificate2->pdf_path, '花子' );
        $this->command?->info( 'DownloadSeeder: ダウンロード確認用の修了証2件を投入しました。' );
        $this->command?->info( '一郎 → ' . $certification1->name . ' → コーチ1' );
        $this->command?->info( '花子 → ' . $certification2->name . ' → コーチ2' );
        $this->command?->info( 'PDF実体: OK' );
    }

    /**
     * 資格をコーチに担当付けする。
     */
    private function assignCoach(
        User $coach,
        Certification $certification,
        User $admin,
    ): void {
        $exists = $coach->assignedCertifications()
            ->whereKey($certification->id)
            ->exists();

        if ($exists) {
            return;
        }

        $coach->assignedCertifications()->attach(
            $certification->id,
            [
                'id' => (string) Str::ulid(),
                'assigned_by_user_id' => $admin->id,
                'assigned_at' => now(),
                'unassigned_at' => null,
            ]
        );
    }

    /**
     * コーチの資格担当を解除する。
     */
    private function unassignCoach(
        User $coach,
        Certification $certification,
    ): void {
        $coach->assignedCertifications()
            ->detach($certification->id);
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

    /*
     * PDFが存在しない場合は生成する。
     */
    if (
        $certificate->pdf_path === null
        || ! Storage::disk('local')->exists($certificate->pdf_path)
    ) {
        app(\App\Services\CertificatePdfService::class)
            ->generate(
                $certificate,
                $certificate->pdf_path
            );
    }

    $certificate->refresh();

    /*
     * PDF生成に失敗していたらSeederを失敗させる。
     */
    if (
        $certificate->pdf_path === null
        || ! Storage::disk('local')->exists($certificate->pdf_path)
    ) {
        throw new \RuntimeException(
            '修了証PDFの生成に失敗しました。'
            . ' enrollment_id=' . $enrollment->id
        );
    }

    return $certificate;
}
}