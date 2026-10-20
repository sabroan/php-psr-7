<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use PHPUnit\Framework\Attributes\CoversClass;
use Sabroan\Http\Message\Request;

#[CoversClass(Request::class)]
final class RequestTest extends \Http\Psr7Test\RequestIntegrationTest
{
    use BaseTrait;

    public function createSubject(): \Psr\Http\Message\RequestInterface
    {
        return new \Sabroan\Http\Message\Request()
            ->withMethod('GET')
            ->withUri($this->buildUri(''));
    }
}
