<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Sabroan\Http\Message\ServerRequest;

#[CoversClass(ServerRequest::class)]
final class ServerRequestTest extends TestCase
{
    /**
     * @param array<string, mixed> $serverParams
     */
    private function createServer(array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($serverParams);
    }

    public function testConstructorInitialStateAndDefaults(): void
    {
        $serverParams = [
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_TIME' => 1600000000,
            'HTTP_HOST' => 'localhost',
        ];

        $request = $this->createServer($serverParams);

        self::assertSame($serverParams, $request->getServerParams());
        self::assertSame([], $request->getCookieParams());
        self::assertSame([], $request->getQueryParams());
        self::assertSame([], $request->getUploadedFiles());
        self::assertSame([], $request->getAttributes());
        self::assertNull($request->getParsedBody());
    }

    public function testWithCookieParamsImmutabilityAndState(): void
    {
        $initial = $this->createServer();
        $cookies = ['session_id' => 'xyz789', 'consent' => '1'];

        $modified = $initial->withCookieParams($cookies);

        self::assertNotSame($initial, $modified);
        self::assertSame([], $initial->getCookieParams());
        self::assertSame($cookies, $modified->getCookieParams());
    }

    public function testWithQueryParamsImmutabilityAndState(): void
    {
        $initial = $this->createServer();
        $query = ['page' => '1', 'filter' => 'active'];

        $modified = $initial->withQueryParams($query);

        self::assertNotSame($initial, $modified);
        self::assertSame([], $initial->getQueryParams());
        self::assertSame($query, $modified->getQueryParams());
    }

    /**
     * @param array<array-key, mixed>|object|null $initialBody
     * @param array<array-key, mixed>|object|null $newBody
     */
    #[DataProvider('validParsedBodyProvider')]
    public function testWithParsedBodyAcceptsValidTypesAndChangesState(
        mixed $initialBody,
        mixed $newBody,
    ): void {
        $request = $this->createServer();

        if ($initialBody !== null) {
            $request = $request->withParsedBody($initialBody);
        }

        $modified = $request->withParsedBody($newBody);

        self::assertNotSame($request, $modified);
        self::assertSame($newBody, $modified->getParsedBody());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function validParsedBodyProvider(): array
    {
        return [
            'array to null' => [
                ['existing' => 'data'],
                null,
            ],
            'null to array' => [
                null,
                ['username' => 'alice', 'email' => 'alice@example.com'],
            ],
            'null to object' => [
                null,
                (object) ['id' => 101],
            ],
            'array to object' => [
                ['foo' => 'bar'],
                (object) ['foo' => 'bar'],
            ],
        ];
    }

    /**
     * @param array<string, array{mixed}> $invalidBody
     */
    #[DataProvider('invalidParsedBodyProvider')]
    public function testWithParsedBodyRejectsInvalidTypes(mixed $invalidBody): void
    {
        $request = $this->createServer();

        self::expectException(\InvalidArgumentException::class);
        $request->withParsedBody($invalidBody);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidParsedBodyProvider(): array
    {
        return [
            'string' => ['raw string payload'],
            'integer' => [404],
            'float' => [99.9],
            'boolean' => [true],
        ];
    }

    public function testAttributeLifecycleAndImmutability(): void
    {
        $initial = $this->createServer();

        $withAttribute = $initial->withAttribute('auth_user', 'admin');
        self::assertNotSame($initial, $withAttribute);
        self::assertNull($initial->getAttribute('auth_user'));
        self::assertSame([], $initial->getAttributes());

        self::assertSame('admin', $withAttribute->getAttribute('auth_user'));
        self::assertSame(['auth_user' => 'admin'], $withAttribute->getAttributes());

        $withoutAttribute = $withAttribute->withoutAttribute('auth_user');
        self::assertNotSame($withAttribute, $withoutAttribute);
        self::assertSame('admin', $withAttribute->getAttribute('auth_user'));
        self::assertNull($withoutAttribute->getAttribute('auth_user'));
        self::assertSame([], $withoutAttribute->getAttributes());
    }

    public function testGetAttributeReturnsProvidedDefaultWhenMissing(): void
    {
        $request = $this->createServer();

        self::assertNull($request->getAttribute('undefined_key'));
        self::assertSame('fallback', $request->getAttribute('undefined_key', 'fallback'));
    }

    public function testWithoutAttributeOnMissingKeyPreservesState(): void
    {
        $initial = $this->createServer()->withAttribute('existing', 'value');
        $modified = $initial->withoutAttribute('non_existent');

        self::assertSame(['existing' => 'value'], $modified->getAttributes());
    }

    /**
     * @param array<array-key, mixed> $files
     */
    #[DataProvider('validUploadedFilesProvider')]
    public function testWithUploadedFilesAcceptsValidStructures(array $files): void
    {
        $initial = $this->createServer();
        $modified = $initial->withUploadedFiles($files);

        self::assertNotSame($initial, $modified);
        self::assertSame([], $initial->getUploadedFiles());
        self::assertSame($files, $modified->getUploadedFiles());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function validUploadedFilesProvider(): array
    {
        $stub = self::createStub(UploadedFileInterface::class);

        return [
            'flat array' => [
                ['avatar' => $stub],
            ],
            'nested array structure' => [
                [
                    'download' => [
                        'details' => [
                            $stub,
                            $stub,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $invalidTree
     */
    #[DataProvider('invalidUploadedFilesTreeProvider')]
    public function testWithUploadedFilesRejectsInvalidStructure(array $invalidTree): void
    {
        $request = $this->createServer();

        self::expectException(\InvalidArgumentException::class);
        $request->withUploadedFiles($invalidTree);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidUploadedFilesTreeProvider(): array
    {
        return [
            'plain object' => [
                ['file' => new \stdClass()],
            ],
            'nested non-UploadedFileInterface' => [
                ['media' => ['photos' => ['not_a_file_object']]],
            ],
            'scalar integer' => [
                ['file' => 12345],
            ],
        ];
    }
}
