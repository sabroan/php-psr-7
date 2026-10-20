<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Sabroan\Http\Message\Stream;

/**
 * @api
 */
final class UploadedFile implements UploadedFileInterface
{
    private const ERRORS = [
        \UPLOAD_ERR_OK,
        \UPLOAD_ERR_INI_SIZE,
        \UPLOAD_ERR_FORM_SIZE,
        \UPLOAD_ERR_PARTIAL,
        \UPLOAD_ERR_NO_FILE,
        \UPLOAD_ERR_NO_TMP_DIR,
        \UPLOAD_ERR_CANT_WRITE,
        \UPLOAD_ERR_EXTENSION,
    ];

    private ?StreamInterface $stream = null;
    private bool $moved = false;

    /**
     * @psalm-pure
     */
    public function __construct(
        private readonly string|StreamInterface $source,
        private readonly ?string $name,
        private readonly ?string $type,
        private readonly ?int $size,
        private readonly int $error,
    ) {
        if (!\in_array($error, self::ERRORS, true)) {
            throw new \InvalidArgumentException(
                'Invalid upload error code provided',
            );
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getStream(): StreamInterface
    {
        /** @psalm-suppress UnusedMethodCall */
        $this->ensureValid();

        if ($this->source instanceof StreamInterface) {
            $this->stream = $this->source;
        } elseif (null === $this->stream) {
            $resource = \fopen($this->source, 'rb');
            if (false === $resource) {
                throw new \RuntimeException(
                    "Unable to open file path '{$this->source}' for reading",
                );
            }
            $this->stream = new Stream($resource);
        }

        return $this->stream;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function moveTo(string $targetPath): void
    {
        /** @psalm-suppress UnusedMethodCall */
        $this->ensureValid();

        if ('' === \trim($targetPath)) {
            throw new \InvalidArgumentException('Target path must be a non-empty string');
        }

        if ((bool) \preg_match('/[\x00-\x1F\x7F]/', $targetPath)) {
            throw new \InvalidArgumentException(
                'Target path cannot contain control characters or null bytes',
            );
        }

        $targetDir = \dirname($targetPath);

        if (!\is_dir($targetDir)) {
            throw new \InvalidArgumentException("Target directory '{$targetDir}' does not exist");
        }

        if (!\is_writable($targetDir)) {
            throw new \InvalidArgumentException("Target directory '{$targetDir}' is not writable");
        }

        try {
            if ($this->source instanceof StreamInterface) {
                $this->copyStream($targetPath);
            } else {
                $this->moveFile($targetPath);
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                message: "Failed to move file to '{$targetPath}': {$exception->getMessage()}",
                previous: $exception,
            );
        }

        $this->moved = true;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getSize(): ?int
    {
        return $this->size;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getError(): int
    {
        return $this->error;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    private function moveFile(
        string $targetPath,
    ): void {
        if (!\is_string($this->source)) {
            throw new \LogicException('Upload source is not a file path');
        }

        $this->stream?->close();

        $moved = \is_uploaded_file($this->source)
            ? \move_uploaded_file($this->source, $targetPath)
            : \rename($this->source, $targetPath);

        if (!$moved) {
            throw new \RuntimeException(
                "Unable to move uploaded file to '{$targetPath}'",
            );
        }
    }

    private function copyStream(
        string $targetPath,
    ): void {
        $stream = $this->getStream();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $target = \fopen($targetPath, 'wb');
        if (false === $target) {
            throw new \RuntimeException(
                "Unable to open target path '{$targetPath}' for writing",
            );
        }

        try {
            while (!$stream->eof()) {
                if (false === \fwrite($target, $stream->read(8192))) {
                    throw new \RuntimeException(
                        "Unable to write to target path '{$targetPath}'",
                    );
                }
            }
        } finally {
            \fclose($target);
            $stream->close();
        }
    }

    /**
     * @psalm-capabilities read-props
     */
    private function ensureValid(): void
    {
        if (\UPLOAD_ERR_OK !== $this->error) {
            throw new \RuntimeException(
                "Cannot access file due to upload error code {$this->error}",
            );
        }

        if ($this->moved) {
            throw new \RuntimeException(
                'The target file has already been moved',
            );
        }
    }
}
