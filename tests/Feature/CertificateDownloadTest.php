<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CertificateDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 通常の初期データを投入
        $this->seed();

        // ダウンロード確認用データを投入
        $this->seed(\Database\Seeders\DownloadSeeder::class);
    }

    /**
     * 受講生は自身の修了証をダウンロードできる。
     *
     * 花子 → 日商簿記2級
     */
    public function test_student_can_download_own_certificate(): void
    {
        $student = User::where(
            'email',
            'student-graduated2@certify-lms.test'
        )->firstOrFail();

        $enrollment = Enrollment::where(
            'user_id',
            $student->id
        )->whereHas('certification', function ($query) {
            $query->where('name', '日商簿記 2 級');
        })->firstOrFail();

        $certificate = Certificate::where(
            'enrollment_id',
            $enrollment->id
        )->firstOrFail();

        $this->assertNotNull($certificate->pdf_path);

        $this->assertFileExists(
            storage_path('app/' . $certificate->pdf_path)
        );

        $response = $this->actingAs($student)
            ->get(
                route(
                    'certificates.download',
                    $certificate
                )
            );

        $response->assertOk();
    }

    /**
     * 担当コーチは担当資格の修了証をダウンロードできる。
     *
     * coach2 → 日商簿記2級
     * 花子 → 日商簿記2級
     */
    public function test_assigned_coach_can_download_certificate(): void
    {
        $coach = User::where(
            'email',
            'coach2@certify-lms.test'
        )->firstOrFail();

        $student = User::where(
            'email',
            'student-graduated2@certify-lms.test'
        )->firstOrFail();

        $certification = $coach->assignedCertifications()
            ->where('name', '日商簿記 2 級')
            ->firstOrFail();

        $enrollment = Enrollment::where(
            'user_id',
            $student->id
        )->where(
            'certification_id',
            $certification->id
        )->firstOrFail();

        $certificate = Certificate::where(
            'enrollment_id',
            $enrollment->id
        )->firstOrFail();

        $this->assertNotNull($certificate->pdf_path);

        $this->assertFileExists(
            storage_path('app/' . $certificate->pdf_path)
        );

        $response = $this->actingAs($coach)
            ->get(
                route(
                    'certificates.download',
                    $certificate
                )
            );

        $response->assertOk();
    }

    /**
     * 担当外コーチは修了証をダウンロードできない。
     *
     * coach1 → 日商簿記2級は担当外
     * 花子 → 日商簿記2級
     */
    public function test_unassigned_coach_cannot_download_certificate(): void
    {
        $coach = User::where(
            'email',
            'coach@certify-lms.test'
        )->firstOrFail();

        $student = User::where(
            'email',
            'student-graduated2@certify-lms.test'
        )->firstOrFail();

        $enrollment = Enrollment::where(
            'user_id',
            $student->id
        )->whereHas('certification', function ($query) {
            $query->where('name', '日商簿記 2 級');
        })->firstOrFail();

        $certificate = Certificate::where(
            'enrollment_id',
            $enrollment->id
        )->firstOrFail();

        $response = $this->actingAs($coach)
            ->get(
                route(
                    'certificates.download',
                    $certificate
                )
            );

        $response->assertForbidden();
    }

    /**
     * 管理者は全ての修了証をダウンロードできる。
     */
    public function test_admin_can_download_all_certificates(): void
    {
        $admin = User::where(
            'email',
            'admin@certify-lms.test'
        )->firstOrFail();

        $students = User::whereIn(
            'email',
            [
                'student-graduated@certify-lms.test',
                'student-graduated2@certify-lms.test',
            ]
        )->pluck('id');

        $certificates = Certificate::whereIn(
    'user_id',
    $students
)->get()->filter(function (Certificate $certificate) {
    return $certificate->pdf_path !== null
        && \Illuminate\Support\Facades\Storage::disk('local')
            ->exists($certificate->pdf_path);
})->values();

        $this->assertGreaterThanOrEqual(
            2,
            $certificates->count()
        );

        foreach ($certificates as $certificate) {
            $this->assertNotNull($certificate->pdf_path);

            $this->assertFileExists(
                storage_path('app/' . $certificate->pdf_path)
            );

            $response = $this->actingAs($admin)
                ->get(
                    route(
                        'certificates.download',
                        $certificate
                    )
                );

            $response->assertOk();
        }
    }

    /**
     * 学習中以外のステータスでも修了証をダウンロードできる。
     *
     * 花子 → 日商簿記2級 → Passed
     */
    public function test_certificate_can_be_downloaded_when_enrollment_is_not_learning(): void
    {
        $student = User::where(
            'email',
            'student-graduated2@certify-lms.test'
        )->firstOrFail();

        $enrollment = Enrollment::where(
            'user_id',
            $student->id
        )->whereHas('certification', function ($query) {
            $query->where('name', '日商簿記 2 級');
        })->firstOrFail();

        $certificate = Certificate::where(
            'enrollment_id',
            $enrollment->id
        )->firstOrFail();

        $status = $enrollment->status instanceof \BackedEnum
            ? $enrollment->status->value
            : $enrollment->status;

        $this->assertNotSame(
            'learning',
            $status
        );

        $this->assertNotNull($certificate->pdf_path);

        $this->assertFileExists(
            storage_path('app/' . $certificate->pdf_path)
        );

        $response = $this->actingAs($student)
            ->get(
                route(
                    'certificates.download',
                    $certificate
                )
            );

        $response->assertOk();
    }
}