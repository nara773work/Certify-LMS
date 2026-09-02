<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function download(Certificate $certificate)
    {
        $this->authorize('download', $certificate);

        abort_unless(
            Storage::disk('local')->exists($certificate->pdf_path),
            404
        );

        return Storage::disk('local')->download(
            $certificate->pdf_path,
            '修了証.pdf'
        );
    }
}
