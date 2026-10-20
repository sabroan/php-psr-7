<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Sabroan\Http\Message\Stream;

#[CoversClass(Stream::class)]
final class StreamTest extends TestCase
{
    /** @var array<string> */
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

    private function createStream(
        string $content = '',
        string $mode = 'r+',
        bool $closed = false,
    ): StreamInterface {
        if (\str_contains($mode, 'w') && !\str_contains($mode, '+')) {
            $filePath = \tempnam(\sys_get_temp_dir(), 'psr7_');

            if ($filePath === false) {
                throw new \RuntimeException('Failed to create temporary file.');
            }

            $this->tempFiles[] = $filePath;
            $resource = \fopen($filePath, $mode);
        } else {
            $resource = \fopen('php://memory', $mode);
        }

        if ($resource === false) {
            throw new \RuntimeException('Failed to open stream resource.');
        }

        if ($content !== '') {
            \fwrite($resource, $content);

            if (!\str_contains($mode, 'w') || \str_contains($mode, '+')) {
                \rewind($resource);
            }
        }

        $stream = new Stream($resource);

        if ($closed) {
            $stream->close();
        }

        return $stream;
    }

    private function createUnseekableStream(): StreamInterface
    {
        $sockets = \stream_socket_pair(
            \STREAM_PF_UNIX,
            \STREAM_SOCK_STREAM,
            \STREAM_IPPROTO_IP,
        );

        if ($sockets !== false) {
            \fclose($sockets[1]);

            return new Stream($sockets[0]);
        }

        $resource = \fopen('php://stderr', 'w');

        if ($resource === false) {
            throw new \RuntimeException('Failed to open stderr stream.');
        }

        return new Stream($resource);
    }

    #[DataProvider('invalidConstructorResourceProvider')]
    public function testConstructorThrowsExceptionOnInvalidResource(mixed $invalidResource): void
    {
        self::expectException(\InvalidArgumentException::class);

        new Stream($invalidResource);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidConstructorResourceProvider(): array
    {
        return [
            'string' => ['not_a_resource'],
            'integer' => [123],
            'boolean' => [false],
            'null' => [null],
            'array' => [[]],
            'object' => [new \stdClass()],
        ];
    }

    #[DataProvider('validStreamContentProvider')]
    public function testToStringReadsEntireStreamAndIsIdempotent(
        string $content,
        int $initialOffset,
    ): void {
        $stream = $this->createStream($content);

        if ($initialOffset > 0 && $stream->isSeekable()) {
            $stream->seek($initialOffset);
        }

        $result = (string) $stream;

        self::assertSame($content, $result);
        self::assertSame($result, (string) $stream);
        self::assertSame($result, $stream->__toString());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function validStreamContentProvider(): array
    {
        return [
            'empty stream' => ['', 0],
            'standard text stream' => ['Hello, PSR-7 world!', 0],
            'binary stream content' => ["\x00\x01\x02\xFF\xFE", 0],
            'multiline string content' => ["Line 1\nLine 2\r\nLine 3", 0],
            'seeked offset auto-rewinds to start' => ['Rewind check data', 7],
        ];
    }

    #[DataProvider('invalidOrUnreadableStreamProvider')]
    public function testToStringReturnsEmptyStringOnInvalidOrUnreadableStream(string $content, string $mode, bool $closed, bool $detach): void
    {
        $stream = $this->createStream($content, $mode, $closed);
        if ($detach) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::assertSame('', (string) $stream);
        self::assertSame('', $stream->__toString());
    }

    /**
     * @return array<string, array{string|bool}>
     */
    public static function invalidOrUnreadableStreamProvider(): array
    {
        return [
            'closed resource' => ['some data', 'r+', true, false],
            'write-only stream' => ['some data', 'w', false, false],
            'detached stream' => ['some data', 'r+', false, true],
        ];
    }

    public function testCloseResetsStateAndIsIdempotent(): void
    {
        $stream = $this->createStream('test content');

        $stream->close();
        $stream->close();

        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertNull($stream->getSize());
        self::assertNull($stream->detach());
        self::assertSame([], $stream->getMetadata());
    }

    public function testCloseOnDetachedStreamDoesNotCloseOriginalResource(): void
    {
        $stream = $this->createStream('test content');

        $resource = $stream->detach();
        $stream->close();

        self::assertTrue(\is_resource($resource));
        \fclose($resource);
    }

    public function testDetachReturnsResourceAndRendersStreamUnusable(): void
    {
        $stream = $this->createStream('sample data');

        $resource = $stream->detach();

        self::assertIsResource($resource);
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertTrue($stream->eof());
        self::assertNull($stream->getSize());
        self::assertSame([], $stream->getMetadata());
        self::assertNull($stream->detach());

        \fclose($resource);
    }

    #[DataProvider('knownSizeContentProvider')]
    public function testGetSizeReturnsCorrectByteSize(string $content, int $expectedSize): void
    {
        $stream = $this->createStream($content);

        self::assertSame($expectedSize, $stream->getSize());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function knownSizeContentProvider(): array
    {
        return [
            'empty stream' => ['', 0],
            'ascii text stream' => ['Hello PSR-7', 11],
            'multibyte utf-8 string' => ['PHP 🚀', 8],
            'binary stream' => ["\x00\x01\x02\xFF\xFE", 5],
        ];
    }

    public function testGetSizeIsIndependentOfPointerAndUpdatesOnWrite(): void
    {
        $stream = $this->createStream('Initial');

        self::assertSame(7, $stream->getSize());
        $stream->seek(3);
        self::assertSame(7, $stream->getSize());

        $stream->seek(0, \SEEK_END);
        $stream->write(' content');

        self::assertSame(15, $stream->getSize());
    }

    #[DataProvider('invalidStreamStateProvider')]
    public function testGetSizeReturnsNullWhenClosedOrDetached(bool $closed, bool $detached): void
    {
        $stream = $this->createStream('some content', 'r+', $closed);
        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::assertNull($stream->getSize());
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function invalidStreamStateProvider(): array
    {
        return [
            'closed' => [true, false],
            'detached' => [false, true],
        ];
    }

    #[DataProvider('capabilityProvider')]
    public function testStreamCapabilities(string $mode, bool $closed, bool $detached, bool $unseekable, bool $expectedReadable, bool $expectedWritable, bool $expectedSeekable): void
    {
        if ($unseekable) {
            $stream = $this->createUnseekableStream();
        } else {
            $stream = $this->createStream('test', $mode, $closed);
        }

        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::assertSame($expectedReadable, $stream->isReadable());
        self::assertSame($expectedWritable, $stream->isWritable());
        self::assertSame($expectedSeekable, $stream->isSeekable());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function capabilityProvider(): array
    {
        return [
            'read-only' => ['r', false, false, false, true, false, true],
            'read-write' => ['r+', false, false, false, true, true, true],
            'write-only' => ['w', false, false, false, false, true, true],
            'write-read' => ['w+', false, false, false, true, true, true],
            'append-read' => ['a+', false, false, false, true, true, true],
            'closed stream' => ['r+', true, false, false, false, false, false],
            'detached stream' => ['r+', false, true, false, false, false, false],
            'unseekable stream' => ['r+', false, false, true, true, true, false],
        ];
    }

    #[DataProvider('tellSeekPositionsProvider')]
    public function testTellAndSeek(int $offset, int $whence, int $expectedPosition): void
    {
        $stream = $this->createStream('0123456789');

        $stream->seek($offset, $whence);

        self::assertSame($expectedPosition, $stream->tell());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function tellSeekPositionsProvider(): array
    {
        return [
            'seek set to beginning' => [0, \SEEK_SET, 0],
            'seek set to middle' => [5, \SEEK_SET, 5],
            'seek set to end' => [0, \SEEK_END, 10],
            'seek cur offset' => [3, \SEEK_CUR, 3],
            'seek end relative position' => [-2, \SEEK_END, 8],
        ];
    }

    public function testTellAdvancesPositionOnReadAndWrite(): void
    {
        $stream = $this->createStream('0123456789');

        self::assertSame(0, $stream->tell());
        $stream->read(4);
        self::assertSame(4, $stream->tell());
        $stream->write('abcd');
        self::assertSame(8, $stream->tell());
    }

    public function testRewindResetsPointerToBeginning(): void
    {
        $stream = $this->createStream('Hello World');

        $stream->seek(5);
        $stream->rewind();

        self::assertSame(0, $stream->tell());
    }

    #[DataProvider('unseekableOrInvalidStreamProvider')]
    public function testSeekAndRewindThrowExceptionOnInvalidState(bool $closed, bool $detached, bool $unseekable, bool $invalidOffset): void
    {
        if ($unseekable) {
            $stream = $this->createUnseekableStream();
        } else {
            $stream = $this->createStream('0123456789', 'r+', $closed);
        }

        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::expectException(\RuntimeException::class);

        if ($invalidOffset) {
            $stream->seek(-100, \SEEK_SET);
        } else {
            $stream->rewind();
        }
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function unseekableOrInvalidStreamProvider(): array
    {
        return [
            'closed stream rewind' => [true, false, false, false],
            'detached stream rewind' => [false, true, false, false],
            'unseekable stream rewind' => [false, false, true, false],
            'invalid offset seek' => [false, false, false, true],
        ];
    }

    public function testTellThrowsExceptionWhenInvalidState(): void
    {
        $stream = $this->createStream('test data', 'r+', true);

        self::expectException(\RuntimeException::class);
        $stream->tell();
    }

    #[DataProvider('writeStringProvider')]
    public function testWriteReturnsBytesWrittenAndUpdatesStream(string $input, int $expectedBytes): void
    {
        $stream = $this->createStream('');

        $bytesWritten = $stream->write($input);

        self::assertSame($expectedBytes, $bytesWritten);
        self::assertSame($expectedBytes, $stream->tell());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function writeStringProvider(): array
    {
        return [
            'empty string' => ['', 0],
            'ascii string' => ['Hello World', 11],
            'multibyte utf-8' => ['PHP 🚀', 8],
            'binary string' => ["\x00\x01\x02\xFF", 4],
        ];
    }

    public function testWriteOverwritesContentAtCurrentPosition(): void
    {
        $stream = $this->createStream('Hello World');

        $stream->seek(6);
        $stream->write('PHP');
        $stream->rewind();

        self::assertSame('Hello PHPld', $stream->getContents());
    }

    #[DataProvider('unwritableStreamProvider')]
    public function testWriteThrowsExceptionWhenNotWritable(string $mode, bool $closed, bool $detached): void
    {
        $stream = $this->createStream('content', $mode, $closed);
        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::expectException(\RuntimeException::class);
        $stream->write('data');
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function unwritableStreamProvider(): array
    {
        return [
            'read-only mode' => ['r', false, false],
            'closed stream' => ['r+', true, false],
            'detached stream' => ['r+', false, true],
        ];
    }

    public function testReadBehaviorAndEofTransition(): void
    {
        $stream = $this->createStream('Hello World');

        self::assertFalse($stream->eof());
        self::assertSame('Hello', $stream->read(5));
        self::assertSame(5, $stream->tell());

        self::assertSame(' World', $stream->read(100));
        self::assertSame(11, $stream->tell());
        self::assertSame('', $stream->read(10));
        self::assertTrue($stream->eof());
    }

    #[DataProvider('unreadableStreamProvider')]
    public function testReadThrowsExceptionWhenNotReadable(string $mode, bool $closed, bool $detached, int $length): void
    {
        $stream = $this->createStream('content', $mode, $closed);
        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::expectException(\RuntimeException::class);
        $stream->read($length);
    }

    /**
     * @return array<string, array{string|bool|int}>
     */
    public static function unreadableStreamProvider(): array
    {
        return [
            'write-only mode' => ['w', false, false, 5],
            'closed stream' => ['r+', true, false, 5],
            'detached stream' => ['r+', false, true, 5],
            'negative length' => ['r+', false, false, -1],
        ];
    }

    public function testGetContentsReturnsRemainingContentFromCurrentPosition(): void
    {
        $stream = $this->createStream('Hello World');

        self::assertSame('Hello World', $stream->getContents());
        self::assertSame('', $stream->getContents());

        $stream->rewind();
        $stream->seek(6);

        self::assertSame('World', $stream->getContents());
    }

    #[DataProvider('unreadableStreamGetContentsProvider')]
    public function testGetContentsThrowsExceptionWhenNotReadable(string $mode, bool $closed, bool $detached): void
    {
        $stream = $this->createStream('content', $mode, $closed);
        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::expectException(\RuntimeException::class);
        $stream->getContents();
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function unreadableStreamGetContentsProvider(): array
    {
        return [
            'write-only mode' => ['w', false, false],
            'closed stream' => ['r+', true, false],
            'detached stream' => ['r+', false, true],
        ];
    }

    public function testGetMetadataReturnsFullArrayOrSpecificKeys(): void
    {
        $stream = $this->createStream('content', 'r+');

        /** @var array<mixed> $metadata */
        $metadata = $stream->getMetadata();

        self::assertNotEmpty($metadata);
        self::assertArrayHasKey('mode', $metadata);
        self::assertArrayHasKey('seekable', $metadata);
        self::assertArrayHasKey('uri', $metadata);
        self::assertIsString($stream->getMetadata('mode'));
        self::assertTrue($stream->getMetadata('seekable'));
        self::assertNull($stream->getMetadata('non_existent_key'));
    }

    #[DataProvider('invalidStreamStateProvider')]
    public function testGetMetadataReturnsEmptyArrayAndNullKeyWhenClosedOrDetached(bool $closed, bool $detached): void
    {
        $stream = $this->createStream('content', 'r+', $closed);
        if ($detached) {
            $resource = $stream->detach();
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }

        self::assertSame([], $stream->getMetadata());
        self::assertNull($stream->getMetadata('mode'));
    }
}
