<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

final class CertificatePdfService
{
    public function generate(
        Certificate $certificate,
        string $path
    ): void {
        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate->load([
                'user',
                'certification',
            ]),
        ]);

        $dompdf = $pdf->getDomPDF();
        $metrics = $dompdf->getFontMetrics();

        $metrics->registerFont(
            [
                'family' => 'NotoSansJP',
                'style' => 'normal',
                'weight' => '400',
            ],
            storage_path('fonts/NotoSansJP-Regular.ttf')
        );

        $metrics->registerFont(
            [
                'family' => 'NotoSansJP',
                'style' => 'normal',
                'weight' => '700',
            ],
            storage_path('fonts/NotoSansJP-Bold.ttf')
        );

        $pdf->setOption([
            'defaultFont' => 'NotoSansJP',
        ]);

        Storage::disk('local')->put(
            $path,
            $pdf->output()
        );
    }
}
