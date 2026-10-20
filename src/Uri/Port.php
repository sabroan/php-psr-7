<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Uri;

use Sabroan\Http\Message\Rfc\Rfc6335;

/**
 * @psalm-immutable
 */
final class Port
{
    /**
     * @psalm-pure
     */
    // @phpstan-ignore pureMethod.void
    public static function ensureValid(int $port): void
    {
        if (!self::inRange($port)) {
            throw new \InvalidArgumentException(\sprintf(
                'Port must be a valid TCP/UDP port between %u and %u',
                Rfc6335::PORT_MIN,
                Rfc6335::PORT_MAX,
            ));
        }
    }

    /**
     * @psalm-pure
     */
    public static function inRange(int $port): bool
    {
        return $port >= Rfc6335::PORT_MIN
            && $port <= Rfc6335::PORT_MAX;
    }
}
