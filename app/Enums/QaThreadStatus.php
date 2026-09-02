<?php

declare(strict_types=1);

namespace App\Enums;

enum QaThreadStatus: string
{
    case Resolved = 'resolved';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Resolved => '解決済み',
            self::Open => '未解決',
        };
    }
}
