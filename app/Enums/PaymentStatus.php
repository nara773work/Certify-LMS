<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Succeeded => '支払い済み',
            self::Failed => '支払いエラー',
            self::Pending => '支払い保留中'
        };
    }
}
