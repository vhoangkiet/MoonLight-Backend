<?php

namespace App\Support\DateTime;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

final class DateTimeFormatter
{
    private function __construct() {}

    public static function toIso8601(CarbonInterface|DateTimeInterface|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::instance($value)->utc()->toIso8601String();
    }

    public static function toDate(CarbonInterface|DateTimeInterface|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::instance($value)->toDateString();
    }

    public static function toDateTime(CarbonInterface|DateTimeInterface|null $value, string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::instance($value)->format($format);
    }
}
