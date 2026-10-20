<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Rfc;

/**
 * @psalm-immutable
 */
final class Rfc3986
{
    /** @link https://datatracker.ietf.org/doc/html/rfc3986#section-2.1 */
    public const string HEXADECIMAL     = '[0-9a-fA-F]{2}';

    /** @link https://datatracker.ietf.org/doc/html/rfc3986#section-2.2 */
    public const string SUB_DELIMS      = "!$&'()*+,;=";

    /** @link https://datatracker.ietf.org/doc/html/rfc3986#section-2.3 */
    public const string UNRESERVED      = 'a-zA-Z0-9\-._~';

    /** @link https://datatracker.ietf.org/doc/html/rfc3986#section-3.1 */
    public const string SCHEME_REGEXP   = '/^[a-zA-Z][a-zA-Z0-9+.-]*$/Di';
}
