<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Rfc;

/**
 * @psalm-immutable
 */
final class Rfc6335
{
    /** @link https://datatracker.ietf.org/doc/html/rfc6335#section-6 */
    public const int PORT_MIN = 0;
    public const int PORT_MAX = 65535;
}
