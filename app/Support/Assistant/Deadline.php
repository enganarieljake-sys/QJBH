<?php

namespace App\Support\Assistant;

/**
 * One time budget for a whole question, across every round, provider, and
 * model, so a slow free AI can't push the request past Vercel's 60 seconds.
 */
class Deadline
{
    private static ?float $until = null;

    public static function start(int $seconds): void
    {
        self::$until = microtime(true) + $seconds;
    }

    public static function stop(): void
    {
        self::$until = null;
    }

    public static function left(): float
    {
        return self::$until === null ? INF : self::$until - microtime(true);
    }

    /**
     * Whether there is still time for one more AI call worth making.
     */
    public static function allows(): bool
    {
        return self::left() > 5;
    }

    /**
     * HTTP timeout for the next call: at most $max seconds, never past the deadline.
     */
    public static function timeout(int $max = 25): int
    {
        return (int) max(5, min($max, floor(self::left())));
    }
}
