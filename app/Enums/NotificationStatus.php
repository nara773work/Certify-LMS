<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationStatus: string
{
    case Unread = 'unread';
    case Read = 'read';

    public function label(): string
    {
        return match ($this) {
            self::Unread => '未読',
            self::Read => '既読',
        };
    }
}
