<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Sabroan\Http\Message\Response;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    private function createResponse(): Response
    {
        return new Response();
    }

    public function testUninitializedStatusCode(): void
    {
        $response = $this->createResponse();

        self::assertSame(0, $response->getStatusCode());
    }

    public function testUninitializedReasonPhrase(): void
    {
        $response = $this->createResponse();

        self::assertSame('', $response->getReasonPhrase());
    }

    public function testUninitializedBodyThrowsException(): void
    {
        $response = $this->createResponse();

        self::expectException(\Throwable::class);
        $body = $response->getBody();
    }

    public function testInitializedState(): void
    {
        $body = self::createStub(StreamInterface::class);
        $response = $this->createResponse()
            ->withStatus(200)
            ->withBody($body);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame('1.1', $response->getProtocolVersion());
        self::assertSame([], $response->getHeaders());
        self::assertSame($body, $response->getBody());
    }

    #[DataProvider('validStatusCodesProvider')]
    public function testWithStatusValid(int $code, string $reason, string $expectedReason): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withStatus($code, $reason);

        self::assertNotSame($response, $newResponse);
        self::assertSame($code, $newResponse->getStatusCode());
        self::assertSame($expectedReason, $newResponse->getReasonPhrase());
    }

    /**
     * @return array<array{int|string}>
     */
    public static function validStatusCodesProvider(): array
    {
        return [
            'standard 200 with default phrase' => [200, '', 'OK'],
            'standard 201 with default phrase' => [201, '', 'Created'],
            'standard 404 with custom phrase' => [404, 'Resource Not Found', 'Resource Not Found'],
            'custom code with phrase' => [599, 'Network Error', 'Network Error'],
            'custom code with empty phrase' => [599, '', ''],
        ];
    }

    #[DataProvider('invalidStatusCodesProvider')]
    public function testWithStatusInvalid(int $code): void
    {
        $response = $this->createResponse();

        self::expectException(\InvalidArgumentException::class);
        $response->withStatus($code);
    }

    /**
     * @return array<array{int}>
     */
    public static function invalidStatusCodesProvider(): array
    {
        return [
            'below minimum range' => [99],
            'above maximum range' => [600],
        ];
    }

    #[DataProvider('validProtocolVersionsProvider')]
    public function testWithProtocolVersionValid(string $version): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withProtocolVersion($version);

        self::assertNotSame($response, $newResponse);
        self::assertSame($version, $newResponse->getProtocolVersion());
    }

    /**
     * @return array<array{string}>
     */
    public static function validProtocolVersionsProvider(): array
    {
        return [
            '1.0' => ['1.0'],
            '1.1' => ['1.1'],
            '2.0' => ['2.0'],
            '3' => ['3'],
        ];
    }

    /**
     * @param string|array<string> $value
     * @param array<string> $expectedHeader
     */
    #[DataProvider('validHeadersProvider')]
    public function testWithHeaderValid(
        string $name,
        string|array $value,
        array $expectedHeader,
        string $expectedLine,
    ): void {
        $response = $this->createResponse();
        $newResponse = $response->withHeader($name, $value);

        self::assertNotSame($response, $newResponse);
        self::assertTrue($newResponse->hasHeader($name));
        self::assertSame($expectedHeader, $newResponse->getHeader($name));
        self::assertSame($expectedLine, $newResponse->getHeaderLine($name));
    }

    /**
     * @return array<array{mixed}>
     */
    public static function validHeadersProvider(): array
    {
        return [
            'single string value' => [
                'Content-Type',
                'application/json',
                ['application/json'],
                'application/json',
            ],
            'array of strings' => [
                'Accept',
                ['text/html', 'application/xhtml+xml'],
                ['text/html', 'application/xhtml+xml'],
                'text/html, application/xhtml+xml',
            ],
            'integer transformed to string' => [
                'X-Count',
                '10',
                ['10'],
                '10',
            ],
        ];
    }

    public function testWithHeaderReplacesExistingHeaderCaseInsensitively(): void
    {
        $response = $this->createResponse()->withHeader('Content-Type', 'text/html');
        $newResponse = $response->withHeader('content-type', 'application/json');

        self::assertNotSame($response, $newResponse);
        self::assertSame(['application/json'], $newResponse->getHeader('Content-Type'));
        self::assertSame('application/json', $newResponse->getHeaderLine('Content-Type'));
    }

    public function testHeaderCaseInsensitivity(): void
    {
        $response = $this->createResponse()->withHeader('Content-Type', 'text/html');

        self::assertTrue($response->hasHeader('content-type'));
        self::assertTrue($response->hasHeader('CONTENT-TYPE'));
        self::assertSame(['text/html'], $response->getHeader('content-type'));
        self::assertSame('text/html', $response->getHeaderLine('CONTENT-TYPE'));
    }

    public function testWithAddedHeaderPreservesExisting(): void
    {
        $response = $this->createResponse()
            ->withHeader('X-Test', 'first')
            ->withAddedHeader('x-test', 'second');

        self::assertSame(['first', 'second'], $response->getHeader('X-Test'));
        self::assertSame('first, second', $response->getHeaderLine('X-Test'));
    }

    public function testWithAddedHeaderCreatesHeaderIfNotPresent(): void
    {
        $response = $this->createResponse()->withAddedHeader('X-Test', 'first');

        self::assertTrue($response->hasHeader('X-Test'));
        self::assertSame(['first'], $response->getHeader('X-Test'));
        self::assertSame('first', $response->getHeaderLine('X-Test'));
    }

    public function testWithoutHeaderRemovesHeader(): void
    {
        $response = $this->createResponse()->withHeader('X-Test', 'value');
        $newResponse = $response->withoutHeader('x-test');

        self::assertNotSame($response, $newResponse);
        self::assertFalse($newResponse->hasHeader('X-Test'));
        self::assertSame([], $newResponse->getHeader('X-Test'));
        self::assertSame('', $newResponse->getHeaderLine('X-Test'));
    }

    public function testWithoutHeaderOnNonExistingHeaderPreservesState(): void
    {
        $response = $this->createResponse()->withHeader('X-Test', 'value');
        $newResponse = $response->withoutHeader('X-Non-Existent');

        self::assertSame(['value'], $newResponse->getHeader('X-Test'));
        self::assertFalse($newResponse->hasHeader('X-Non-Existent'));
    }

    #[DataProvider('invalidHeaderNamesProvider')]
    public function testWithHeaderInvalidName(string $name): void
    {
        $response = $this->createResponse();

        self::expectException(\InvalidArgumentException::class);
        $response->withHeader($name, 'valid-value');
    }

    /**
     * @return array<array{string}>
     */
    public static function invalidHeaderNamesProvider(): array
    {
        return [
            'empty string' => [''],
            'contains carriage return' => ["Header\rName"],
            'contains newline' => ["Header\nName"],
            'contains null byte' => ["Header\0Name"],
        ];
    }

    #[DataProvider('invalidHeaderValuesProvider')]
    public function testWithHeaderInvalidValue(mixed $value): void
    {
        $response = $this->createResponse();

        self::expectException(\TypeError::class);

        // @phpstan-ignore argument.type (Intentionally testing invalid header values.)
        $response->withHeader('X-Test', $value);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidHeaderValuesProvider(): array
    {
        return [
            'array containing integer' => [[123]],
            'array containing object' => [[new \stdClass()]],
        ];
    }

    public function testWithBody(): void
    {
        $response = $this->createResponse();
        $body = self::createStub(StreamInterface::class);

        $newResponse = $response->withBody($body);

        self::assertNotSame($response, $newResponse);
        self::assertSame($body, $newResponse->getBody());
    }
}
