<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\StreamInterface;
use Sabroan\Http\Message\Stream;

#[CoversClass(Stream::class)]
final class StreamTest extends \Http\Psr7Test\StreamIntegrationTest
{
    use BaseTrait;

    public function createStream($data): StreamInterface
    {
        return $this->buildStream($data);
    }
}
