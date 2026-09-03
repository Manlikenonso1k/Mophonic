<?php

namespace App\Support;

/** Everything is stored in kobo; conversion happens once, at the edges. */
class Money
{
    public static function toKobo(float|int|string $naira): int
    {
        return (int) round(((float) $naira) * 100);
    }

    public static function toNaira(int $kobo): float
    {
        return $kobo / 100;
    }

    public static function format(int $kobo): string
    {
        return '₦'.number_format(self::toNaira($kobo), 2);
    }
}
