<?php

namespace App\Support;

class MoneyFormatter
{
    public static function format($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
