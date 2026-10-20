<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Sabroan\Http\Message\Stream;
use Sabroan\Http\Message\UploadedFile;

#[CoversClass(UploadedFile::class)]
final class UploadedFileTest extends TestCase
{
    /** @var array<string>*/
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        /** @var string */
        foreach ($this->tempFiles as $filePath) {
            if (\file_exists($filePath)) {
                \unlink($filePath);
            }
        }

        $this->tempFiles = [];
    }

    private function createTempFilePath(string $prefix = 'psr7_test_'): string
    {
        $path = \sys_get_temp_dir() . '/' . \uniqid($prefix, true) . '.txt';
        $this->tempFiles[] = $path;

        return $path;
    }

    private function createUploadedFile(
        StreamInterface|string $file = '',
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $name = 'filename.txt',
        ?string $type = 'text/plain',
    ): UploadedFileInterface {
        return new UploadedFile(
            source: $file,
            size: $size,
            error: $error,
            name: $name,
            type: $type,
        );
    }

    private function createStream(string $content = ''): StreamInterface
    {
        $resource = \fopen('php://memory', 'r+');

        if ($resource === false) {
            throw new \RuntimeException('Failed to open memory stream.');
        }

        \fwrite($resource, $content);
        \rewind($resource);

        return new Stream($resource);
    }

    public function testGetStream(): void
    {
        $content = 'uploaded file stream content';
        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream($content),
        );

        $stream1 = $uploadedFile->getStream();
        self::assertInstanceOf(StreamInterface::class, $stream1);
        self::assertSame($content, $stream1->getContents());

        $stream2 = $uploadedFile->getStream();
        $stream2->rewind();
        self::assertSame($content, $stream2->getContents());
    }

    public function testGetStreamWithStringFile(): void
    {
        $filePath = $this->createTempFilePath();
        $content = 'file created from string path';
        \file_put_contents($filePath, $content);

        $uploadedFile = $this->createUploadedFile(file: $filePath);

        $stream = $uploadedFile->getStream();

        self::assertInstanceOf(StreamInterface::class, $stream);
        self::assertSame($content, $stream->getContents());
    }

    public function testGetStreamThrowsRuntimeExceptionAfterMoveTo(): void
    {
        $targetPath = $this->createTempFilePath();
        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream('content'),
        );

        $uploadedFile->moveTo($targetPath);

        self::expectException(\RuntimeException::class);
        $uploadedFile->getStream();
    }

    #[DataProvider('uploadErrorCodesExceptOkProvider')]
    public function testGetStreamThrowsRuntimeExceptionWhenErrorIsNotOk(int $error): void
    {
        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream('content'),
            error: $error,
        );

        self::expectException(\RuntimeException::class);
        $uploadedFile->getStream();
    }

    public function testMoveToSuccessfully(): void
    {
        $targetPath = $this->createTempFilePath();
        $content = 'test file content';

        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream($content),
        );

        $uploadedFile->moveTo($targetPath);

        self::assertFileExists($targetPath);
        self::assertSame($content, \file_get_contents($targetPath));
    }

    public function testMoveToThrowsRuntimeExceptionOnSecondCall(): void
    {
        $targetPath1 = $this->createTempFilePath();
        $targetPath2 = $this->createTempFilePath();

        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream('content'),
        );

        $uploadedFile->moveTo($targetPath1);

        self::expectException(\RuntimeException::class);
        $uploadedFile->moveTo($targetPath2);
    }

    #[DataProvider('uploadErrorCodesExceptOkProvider')]
    public function testMoveToThrowsRuntimeExceptionWhenErrorIsNotOk(int $error): void
    {
        $targetPath = $this->createTempFilePath();
        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream('content'),
            error: $error,
        );

        self::expectException(\RuntimeException::class);
        $uploadedFile->moveTo($targetPath);
    }

    #[DataProvider('invalidPathsProvider')]
    public function testMoveToThrowsInvalidArgumentExceptionForInvalidPath(string $invalidPath): void
    {
        $uploadedFile = $this->createUploadedFile(
            file: $this->createStream('content'),
        );

        self::expectException(\InvalidArgumentException::class);
        $uploadedFile->moveTo($invalidPath);
    }

    #[DataProvider('fileSizesProvider')]
    public function testGetSize(?int $size): void
    {
        $uploadedFile = $this->createUploadedFile(size: $size);

        self::assertSame($size, $uploadedFile->getSize());
    }

    #[DataProvider('uploadErrorCodesProvider')]
    public function testGetError(int $error): void
    {
        $uploadedFile = $this->createUploadedFile(error: $error);

        self::assertSame($error, $uploadedFile->getError());
    }

    #[DataProvider('clientFilenamesProvider')]
    public function testGetClientFilename(?string $filename): void
    {
        $uploadedFile = $this->createUploadedFile(name: $filename);

        self::assertSame($filename, $uploadedFile->getClientFilename());
    }

    #[DataProvider('clientMediaTypesProvider')]
    public function testGetClientMediaType(?string $mediaType): void
    {
        $uploadedFile = $this->createUploadedFile(type: $mediaType);

        self::assertSame($mediaType, $uploadedFile->getClientMediaType());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPathsProvider(): array
    {
        return [
            'empty path' => [''],
            'null byte in path' => ["/tmp/file\0.txt"],
            'non-existent directory' => ['/non_existent_directory/cannot_write_here.txt'],
        ];
    }

    /**
     * @return array<string, array{?int}>
     */
    public static function fileSizesProvider(): array
    {
        return [
            'null size' => [null],
            'zero bytes' => [0],
            'positive size' => [1024],
        ];
    }

    /**
     * @return array<string, array{int}>
     */
    public static function uploadErrorCodesProvider(): array
    {
        return [
            'UPLOAD_ERR_OK' => [\UPLOAD_ERR_OK],
            'UPLOAD_ERR_INI_SIZE' => [\UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE' => [\UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL' => [\UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE' => [\UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [\UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [\UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION' => [\UPLOAD_ERR_EXTENSION],
        ];
    }

    /**
     * @return array<string, array{int}>
     */
    public static function uploadErrorCodesExceptOkProvider(): array
    {
        return \array_filter(
            self::uploadErrorCodesProvider(),
            static fn(array $item): bool => $item[0] !== \UPLOAD_ERR_OK,
        );
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function clientFilenamesProvider(): array
    {
        return [
            'null filename' => [null],
            'standard filename' => ['document.pdf'],
            'empty string filename' => [''],
        ];
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function clientMediaTypesProvider(): array
    {
        return [
            'null media type' => [null],
            'standard media type' => ['text/plain'],
            'empty string media type' => [''],
        ];
    }
}
