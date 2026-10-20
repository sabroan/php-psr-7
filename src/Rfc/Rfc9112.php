<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Rfc;

/**
 * @psalm-immutable
 */
final class Rfc9112
{
    /** @link https://datatracker.ietf.org/doc/html/rfc9112#section-2.3 */
    public const string PROTOCOL_VERSION_REGEXP = "/^[0-9]+(?:\.[0-9]+)?$/D";

    /** @link https://datatracker.ietf.org/doc/html/rfc9112#section-3.2 */
    public const string REQUEST_TARGET_REGEXP = "#^(?:%[0-9a-fA-F]{2}|[][a-zA-Z0-9._~!$&'()*+,;=:@/?-])+$#D";
}
