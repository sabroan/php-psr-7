<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use PHPUnit\Framework\Attributes\CoversClass;
use Sabroan\Http\Message\Response;

#[CoversClass(Response::class)]
final class ResponseTest extends \Http\Psr7Test\ResponseIntegrationTest
{
    use BaseTrait;

    public function createSubject(): \Psr\Http\Message\ResponseInterface
    {
        return new \Sabroan\Http\Message\Response();
    }
}
