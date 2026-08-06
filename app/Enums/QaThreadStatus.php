<?php

namespace App\Enums;

enum QaThreadStatus: string
{
    case Resolved = 'resolved';
    case UnResolved = 'unresolved';

    public function label(): string{
        return match ($this) {
            self::Resolved => '解決済み',
            self::UnResolved => '未解決',
        };
    }

}