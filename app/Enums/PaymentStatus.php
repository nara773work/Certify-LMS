<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string{
        return match ($this) {
            self::Succeeded => '支払い済み',
            self::false => '支払いエラー',
        };
    }

}