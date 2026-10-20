<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

trait BaseTrait
{
    /**
     * @param mixed $data
     */
    protected function buildStream($data): StreamInterface
    {
        if ($data instanceof StreamInterface) {
            return $data;
        }
        if (\is_string($data)) {
            $resource = \fopen('php://memory', 'r+');

            if ($resource === false) {
                throw new \RuntimeException('Failed to open memory stream');
            }

            \fwrite($resource, $data);
            \rewind($resource);
            $data = $resource;
        }

        if (!\is_resource($data)) {
            throw new \InvalidArgumentException(
                'Stream data must be a resource, string, or StreamInterface',
            );
        }

        return new \Sabroan\Http\Message\Stream($data);
    }

    /**
     * @param mixed $uri
     */
    protected function buildUri($uri): UriInterface
    {
        if (!\is_string($uri)) {
            throw new \InvalidArgumentException('URI must be a string');
        }

        $parts = \parse_url($uri);
        $parts = \is_array($parts) ? $parts : [];

        return (new \Sabroan\Http\Message\Uri())
            ->withScheme($parts['scheme'] ?? '')
            ->withHost($parts['host'] ?? '')
            ->withUserInfo(
                $parts['user'] ?? '',
                $parts['pass'] ?? null,
            )
            ->withPort($parts['port'] ?? null)
            ->withPath($parts['path'] ?? '')
            ->withQuery($parts['query'] ?? '')
            ->withFragment($parts['fragment'] ?? '');
    }

    /**
     * @param mixed $data
     */
    protected function buildUploadableFile($data): UploadedFileInterface
    {
        $stream = $this->buildStream($data);
        return new \Sabroan\Http\Message\UploadedFile(
            source: $stream,
            size: $stream->getSize(),
            error: \UPLOAD_ERR_OK,
            name: 'filename.txt',
            type: 'text/plain',
        );
    }
}
