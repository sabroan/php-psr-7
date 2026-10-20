<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Rfc;

/**
 * @psalm-immutable
 */
final class IanaSchemePort
{
    /** @link https://www.iana.org/assignments/service-names-port-numbers */
    public const DEFAULT = [
        'http'  => 80,
        'https' => 443,
        'ws'    => 80,
        'wss'   => 443,
    ];
}
