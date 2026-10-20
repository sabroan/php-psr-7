<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use PHPUnit\Framework\Attributes\CoversClass;
use Sabroan\Http\Message\Uri;

#[CoversClass(Uri::class)]
final class UriTest extends \Http\Psr7Test\UriIntegrationTest
{
    use BaseTrait;

    public function createUri($uri): \Psr\Http\Message\UriInterface
    {
        return $this->buildUri($uri);
    }
}
