<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Sabroan\Http\Message\Request;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    private function createRequest(): Request
    {
        return new Request();
    }

    public function testInitialState(): void
    {
        $request = $this->createRequest();

        self::assertSame('', $request->getMethod());
        self::assertSame('/', $request->getRequestTarget());
        self::assertSame([], $request->getHeaders());
        self::assertSame('1.1', $request->getProtocolVersion());
    }

    #[DataProvider('validMethodProvider')]
    public function testWithMethodReturnsNewInstanceWithMethod(string $method): void
    {
        $initial = $this->createRequest();
        $modified = $initial->withMethod($method);

        self::assertNotSame($initial, $modified);
        self::assertSame($method, $modified->getMethod());
    }

    /**
     * @return array<array{string}>
     */
    public static function validMethodProvider(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
            'PURGE' => ['PURGE'],
            'CUSTOM_WITH_ALLOWED_CHARACTERS' => ['TRACE-CUSTOM!'],
        ];
    }

    #[DataProvider('invalidMethodProvider')]
    public function testWithMethodRejectsInvalidMethods(string $invalidMethod): void
    {
        $request = $this->createRequest();

        self::expectException(\InvalidArgumentException::class);
        $request->withMethod($invalidMethod);
    }

    /**
     * @return array<array{string}>
     */
    public static function invalidMethodProvider(): array
    {
        return [
            'empty string' => [''],
            'whitespace only' => ['   '],
            'contains space' => ['GET USER'],
            'contains invalid control char' => ["GET\0"],
            'contains non-token bracket' => ['GET[1]'],
        ];
    }

    public function testWithRequestTargetImmutabilityAndState(): void
    {
        $initial = $this->createRequest();
        $target = '/api/v1/resource?query=1';

        $modified = $initial->withRequestTarget($target);

        self::assertNotSame($initial, $modified);
        self::assertSame('/', $initial->getRequestTarget());
        self::assertSame($target, $modified->getRequestTarget());
    }

    #[DataProvider('invalidRequestTargetProvider')]
    public function testWithRequestTargetRejectsInvalidTarget(string $invalidTarget): void
    {
        $request = $this->createRequest();

        self::expectException(\InvalidArgumentException::class);
        $request->withRequestTarget($invalidTarget);
    }

    /**
     * @return array<array{string}>
     */
    public static function invalidRequestTargetProvider(): array
    {
        return [
            'contains whitespace' => ['/api /v1'],
            'contains control character' => ["/api\0/v1"],
        ];
    }

    public function testRequestTargetOriginatingFromUri(): void
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/path');
        $uri->method('getQuery')->willReturn('foo=bar');

        $request = $this->createRequest()->withUri($uri);

        self::assertSame('/path?foo=bar', $request->getRequestTarget());
    }

    public function testWithUriImmutabilityAndHostHeaderBehavior(): void
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPort')->willReturn(null);

        $initial = $this->createRequest();
        $modified = $initial->withUri($uri);

        self::assertNotSame($initial, $modified);
        self::assertSame($uri, $modified->getUri());
        self::assertSame(['example.com'], $modified->getHeader('Host'));
    }

    public function testWithUriPreservesHostWhenRequested(): void
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('newhost.com');
        $uri->method('getPort')->willReturn(null);

        $initial = $this->createRequest()->withHeader('Host', 'original.com');
        $modified = $initial->withUri($uri, true);

        self::assertSame(['original.com'], $modified->getHeader('Host'));
    }

    public function testWithUriUpdatesHostWhenPreserveHostIsTrueButHeaderMissing(): void
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('newhost.com');
        $uri->method('getPort')->willReturn(null);

        $initial = $this->createRequest();
        $modified = $initial->withUri($uri, true);

        self::assertSame(['newhost.com'], $modified->getHeader('Host'));
    }

    public function testWithUriIncludesPortInHostHeaderWhenNonStandard(): void
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPort')->willReturn(8080);

        $request = $this->createRequest()->withUri($uri);

        self::assertSame(['example.com:8080'], $request->getHeader('Host'));
    }

    public function testWithProtocolVersionImmutabilityAndState(): void
    {
        $initial = $this->createRequest();
        $modified = $initial->withProtocolVersion('2.0');

        self::assertNotSame($initial, $modified);
        self::assertSame('1.1', $initial->getProtocolVersion());
        self::assertSame('2.0', $modified->getProtocolVersion());
    }

    #[DataProvider('invalidProtocolVersionProvider')]
    public function testWithProtocolVersionRejectsInvalidVersions(string $invalidVersion): void
    {
        $request = $this->createRequest();

        self::expectException(\InvalidArgumentException::class);
        $request->withProtocolVersion($invalidVersion);
    }

    /**
     * @return array<array{string}>
     */
    public static function invalidProtocolVersionProvider(): array
    {
        return [
            'empty string' => [''],
            'non-numeric string' => ['custom'],
            'contains HTTP prefix' => ['HTTP/1.1'],
            'contains trailing whitespace' => ['1.1 '],
        ];
    }

    /**
     * @param string|array<string> $value
     * @param array<string> $expectedHeader
     */
    #[DataProvider('validHeaderProvider')]
    public function testHeaderLifecycleAndCaseInsensitivity(
        string $name,
        mixed $value,
        array $expectedHeader,
        string $expectedLine,
    ): void {
        $initial = $this->createRequest();
        $modified = $initial->withHeader($name, $value);

        self::assertNotSame($initial, $modified);
        self::assertFalse($initial->hasHeader($name));
        self::assertTrue($modified->hasHeader($name));
        self::assertTrue($modified->hasHeader(strtolower($name)));
        self::assertTrue($modified->hasHeader(strtoupper($name)));

        self::assertSame($expectedHeader, $modified->getHeader($name));
        self::assertSame($expectedLine, $modified->getHeaderLine($name));
    }

    /**
     * @return array<array{string|array{string}}>
     */
    public static function validHeaderProvider(): array
    {
        return [
            'single value string' => [
                'Content-Type',
                'application/json',
                ['application/json'],
                'application/json',
            ],
            'array of values' => [
                'Accept',
                ['text/html', 'application/xhtml+xml'],
                ['text/html', 'application/xhtml+xml'],
                'text/html, application/xhtml+xml',
            ],
        ];
    }

    public function testWithAddedHeaderAppendsValues(): void
    {
        $initial = $this->createRequest()->withHeader('Accept', 'text/html');
        $modified = $initial->withAddedHeader('accept', 'application/json');

        self::assertNotSame($initial, $modified);
        self::assertSame(['text/html'], $initial->getHeader('Accept'));
        self::assertSame(
            ['text/html', 'application/json'],
            $modified->getHeader('Accept'),
        );
        self::assertSame(
            'text/html, application/json',
            $modified->getHeaderLine('Accept'),
        );
    }

    public function testWithoutHeaderRemovesHeaderCaseInsensitively(): void
    {
        $initial = $this->createRequest()->withHeader('X-Foo', 'Bar');
        $modified = $initial->withoutHeader('x-foo');

        self::assertNotSame($initial, $modified);
        self::assertTrue($initial->hasHeader('X-Foo'));
        self::assertFalse($modified->hasHeader('X-Foo'));
        self::assertSame([], $modified->getHeader('X-Foo'));
    }

    #[DataProvider('invalidHeaderNameProvider')]
    public function testWithHeaderRejectsInvalidNames(string $invalidName): void
    {
        $request = $this->createRequest();

        self::expectException(\InvalidArgumentException::class);
        $request->withHeader($invalidName, 'value');
    }

    /**
     * @return array<array{string}>
     */
    public static function invalidHeaderNameProvider(): array
    {
        return [
            'empty string' => [''],
            'contains space' => ['X Header'],
            'contains colon' => ['Header:Name'],
            'contains control character' => ["Header\0Name"],
        ];
    }

    #[DataProvider('invalidHeaderValueProvider')]
    public function testWithHeaderRejectsInvalidValues(mixed $invalidValue): void
    {
        $request = $this->createRequest();

        self::expectException(\InvalidArgumentException::class);
        // @phpstan-ignore argument.type (Intentionally testing invalid header values.)
        $request->withHeader('X-Test', $invalidValue);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidHeaderValueProvider(): array
    {
        return [
            'string with invalid control chars' => ["value\0with-null"],
        ];
    }

    #[DataProvider('invalidHeaderTypeProvider')]
    public function testWithHeaderRejectsInvalidTypes(mixed $invalidValue): void
    {
        $request = $this->createRequest();

        self::expectException(\TypeError::class);
        // @phpstan-ignore argument.type (Intentionally testing invalid header types.)
        $request->withHeader('X-Test', $invalidValue);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidHeaderTypeProvider(): array
    {
        return [
            'integer' => [123],
            'boolean' => [true],
            'object' => [new \stdClass()],
            'array containing non-string' => [['valid', 123]],
        ];
    }

    public function testWithBodyImmutabilityAndState(): void
    {
        $initial = $this->createRequest();
        $stream = self::createStub(StreamInterface::class);

        $modified = $initial->withBody($stream);

        self::assertNotSame($initial, $modified);
        self::assertSame($stream, $modified->getBody());
    }
}
