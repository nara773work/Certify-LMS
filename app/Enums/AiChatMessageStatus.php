<?php

declare(strict_types=1);

namespace App\Enums;

enum AiChatMessageStatus: string
{
    case Error = 'error';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Error => 'エラー',
            self::Pending => '保留',
        };
    }
}
