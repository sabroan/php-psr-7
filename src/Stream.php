<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * @api
 */
final class Stream implements StreamInterface
{
    private mixed $resource;
    private bool $seekable;
    private bool $readable;
    private bool $writable;

    /**
     * @param mixed $resource
     * @return void
     * @link https://www.php.net/manual/en/wrappers.php
     * @link https://www.php.net/manual/en/function.fopen.php
     **/
    public function __construct(mixed $resource)
    {
        if (
            !\is_resource($resource)
            || 'stream' !== \get_resource_type($resource)
        ) {
            throw new \InvalidArgumentException(
                'Stream must be a valid resource',
            );
        }
        $this->resource = $resource;
        /** @var string $mode */
        $mode = $this->getMetadata('mode') ?? '';
        $this->readable = (bool) \strpbrk($mode, 'r+');
        $this->writable = (bool) \strpbrk($mode, 'w+acx');
        $this->seekable = (bool) $this->getMetadata('seekable');
    }

    /** @return void */
    public function __destruct()
    {
        $this->close();
    }

    /** {@inheritDoc} */
    #[\Override]
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function close(): void
    {
        if (!isset($this->resource)) {
            return;
        }
        $handle = $this->detach();
        if (\is_resource($handle)) {
            \fclose($handle);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[\Override]
    public function detach(): mixed
    {
        /** @var ?resource */
        $resource = $this->resource ?? null;
        $this->resource = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $resource;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getSize(): ?int
    {
        if (!\is_resource($this->resource)) {
            return null;
        }

        if ($this->getMetadata('uri') === 'php://input') {
            return null;
        }

        if (\in_array(
            $this->getMetadata('wrapper_type'),
            ['http', 'https', 'ftp', 'ftps'],
            true,
        )) {
            return null;
        }

        $stats = \fstat($this->resource);
        if (\is_array($stats)) {
            return $stats['size'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props, io
     */
    #[\Override]
    public function tell(): int
    {
        if (!\is_resource($this->resource)) {
            throw new \RuntimeException(
                'Stream is detached or invalid',
            );
        }

        $position = \ftell($this->resource);
        if (false !== $position) {
            return $position;
        }

        throw new \RuntimeException(
            'Unable to determine stream position',
        );
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props, io
     */
    #[\Override]
    public function eof(): bool
    {
        return !\is_resource($this->resource)
            || \feof($this->resource);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function isSeekable(): bool
    {
        return \is_resource($this->resource) && $this->seekable;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function seek(int $offset, int $whence = \SEEK_SET): void
    {
        if (!\is_resource($this->resource)) {
            throw new \RuntimeException(
                'Stream is detached or invalid',
            );
        }

        if (!$this->isSeekable()) {
            throw new \RuntimeException(
                'Stream is not seekable',
            );
        }

        if (-1 === \fseek(
            $this->resource,
            $offset,
            $whence,
        )) {
            throw new \RuntimeException(
                'Unable to seek to stream position',
            );
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function isWritable(): bool
    {
        return \is_resource($this->resource) && $this->writable;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function write(string $string): int
    {
        if (!\is_resource($this->resource)) {
            throw new \RuntimeException(
                'Stream is detached or invalid',
            );
        }

        if (!$this->isWritable()) {
            throw new \RuntimeException(
                'Stream is not writable',
            );
        }

        $result = \fwrite($this->resource, $string);
        if (false !== $result) {
            return $result;
        }

        throw new \RuntimeException(
            'Unable to write to stream',
        );
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function isReadable(): bool
    {
        return \is_resource($this->resource) && $this->readable;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function read(int $length): string
    {
        if (!\is_resource($this->resource)) {
            throw new \RuntimeException(
                'Stream is detached or closed',
            );
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException(
                'Stream is not readable',
            );
        }

        if ($length < 0) {
            throw new \RuntimeException(
                'Length parameter must be a non-negative integer',
            );
        }

        if (0 === $length) {
            return '';
        }

        $content = \fread($this->resource, $length);

        if (false !== $content) {
            return $content;
        }

        throw new \RuntimeException(
            'Unable to read from stream',
        );
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getContents(): string
    {
        if (!\is_resource($this->resource)) {
            throw new \RuntimeException(
                'Stream is detached or invalid',
            );
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException(
                'Stream is not readable',
            );
        }

        $result = \stream_get_contents($this->resource);
        if (false !== $result) {
            return $result;
        }

        throw new \RuntimeException(
            'Error reading stream',
        );
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getMetadata(?string $key = null): mixed
    {
        if (!\is_resource($this->resource)) {
            return null !== $key ? null : [];
        }

        $meta = \stream_get_meta_data($this->resource);

        if (null === $key) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
