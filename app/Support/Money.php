<?php

namespace App\Support;

use Illuminate\Support\Number;

class Money
{
    /**
     * Format an amount in pence as pounds sterling, dropping ".00" for whole pounds.
     */
    public static function format(int $pence): string
    {
        $precision = $pence % 100 === 0 ? 0 : 2;

        return Number::currency($pence / 100, 'GBP', 'en_GB', $precision);
    }
}
