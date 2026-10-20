<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Rfc;

/**
 * @psalm-immutable
 */
final class Rfc9110
{
    /** @link https://datatracker.ietf.org/doc/html/rfc9110#section-5.6.2 */
    public const string TOKEN_REGEXP    = '/^[!#$%&\'*+^`|a-zA-Z0-9\-._~]+$/Di';

    /** @link https://datatracker.ietf.org/doc/html/rfc9110#section-5.5 */
    public const string VALUE_REGEXP    = '/^(?:[\x21-\x7E\x80-\xFF](?:[\x09\x20]*[\x21-\x7E\x80-\xFF])*)?$/D';

    /** @link https://datatracker.ietf.org/doc/html/rfc9110#section-15 */
    public const int CODE_MIN = 100;
    public const int CODE_MAX = 599;
}
