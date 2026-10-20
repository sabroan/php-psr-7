<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Request;

use Sabroan\Http\Message\Rfc\Rfc9110;

/**
 * @psalm-immutable
 */
final class Method
{
    private const array METHODS = [
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PURGE',
        'PUT',
        'QUERY',
        'TRACE',
    ];

    /**
     * @psalm-pure
     */
    public static function isHttp(string $method): bool
    {
        return \in_array(
            \strtoupper($method),
            self::METHODS,
            true,
        );
    }

    /**
     * @psalm-pure
     */
    // @phpstan-ignore pureMethod.void
    public static function ensureValid(string $method): void
    {
        if (static::isHttp($method)) {
            return;
        }

        // @phpstan-ignore possiblyImpure.functionCall
        if (false === (bool) \preg_match(Rfc9110::TOKEN_REGEXP, $method)) {
            throw new \InvalidArgumentException("Method '{$method}' not allowed by RFC 9110");
        }
    }
}
