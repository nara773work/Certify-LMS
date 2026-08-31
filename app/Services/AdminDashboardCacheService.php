<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class AdminDashboardCacheService
{
    public function forget(): void
    {
        Cache::forget('admin_dashboard.kpi');
        Cache::forget('admin_dashboard.completion_rate_by_certification');
    }
}