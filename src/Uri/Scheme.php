<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Uri;

use Sabroan\Http\Message\Rfc\Rfc3986;

/**
 * @psalm-immutable
 */
final class Scheme
{
    /**
     * @psalm-pure
     */
    // @phpstan-ignore pureMethod.void
    public static function ensureValid(string $scheme): void
    {
        if (!self::isValid($scheme)) {
            throw new \InvalidArgumentException(
                "Scheme '{$scheme}' not allowed by RFC 3986",
            );
        }
    }

    /**
     * @psalm-pure
     */
    private static function isValid(string $value): bool
    {
        // @phpstan-ignore possiblyImpure.functionCall
        return (bool) \preg_match(
            Rfc3986::SCHEME_REGEXP,
            $value,
        );
    }
}
