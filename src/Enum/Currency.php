<?php
declare(strict_types=1);

namespace App\Enum;

enum Currency: string
{
    case EUR = 'eur';
    case USD = 'usd';
    case CHF = 'chf';

    public static function fromInput(string $code): self
    {
        return self::from(strtolower($code));
    }
}
