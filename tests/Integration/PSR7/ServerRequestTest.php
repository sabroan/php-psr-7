<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use PHPUnit\Framework\Attributes\CoversClass;
use Sabroan\Http\Message\ServerRequest;

#[CoversClass(ServerRequest::class)]
final class ServerRequestTest extends \Http\Psr7Test\ServerRequestIntegrationTest
{
    use BaseTrait;

    public function createSubject(): \Psr\Http\Message\ServerRequestInterface
    {
        return new \Sabroan\Http\Message\ServerRequest($_SERVER)
            ->withMethod('GET')
            ->withUri($this->buildUri(''));
    }
}
