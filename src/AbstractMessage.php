<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Sabroan\Http\Message\Message\Header;
use Sabroan\Http\Message\Rfc\Rfc9112;

/**
 * @api
 */
abstract class AbstractMessage implements MessageInterface
{
    /**
     * @var array<lowercase-string, array{
     *     name: string,
     *     values: non-empty-list<string>
     * }>
     */
    private array $headers = [];
    private string $version = '1.1';
    private ?StreamInterface $body = null;

    /** {@inheritDoc} */
    #[\Override]
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withProtocolVersion(string $version): MessageInterface
    {
        if (! (bool) \preg_match(Rfc9112::PROTOCOL_VERSION_REGEXP, $version)) {
            throw new \InvalidArgumentException(
                "Protocol version '{$version}' not allowed by RFC 9112",
            );
        }

        return clone($this, ['version' => $version]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, non-empty-list<string>>
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function getHeaders(): array
    {
        return \array_column($this->headers, 'values', 'name');
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[\strtolower($name)]);
    }

    /**
     * {@inheritDoc}
     *
     * @return list<string>
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function getHeader(string $name): array
    {
        return $this->headers[\strtolower($name)]['values'] ?? [];
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function getHeaderLine(string $name): string
    {
        return \implode(', ', $this->getHeader($name));
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withHeader(string $name, mixed $value): MessageInterface
    {
        return $this
            ->withoutHeader($name)
            ->withAddedHeader($name, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withAddedHeader(string $name, mixed $value): MessageInterface
    {
        Header::validateName($name);

        $key    = \strtolower($name);
        $value  = Header::toArray($value);
        $value  = Header::toValues($value);

        if ([] === $value) {
            throw new \InvalidArgumentException('Header value cannot be empty');
        }

        if (isset($this->headers[$key])) {
            $name   = $this->headers[$key]['name'];
            $value  = [
                ...$this->headers[$key]['values'],
                ...$value,
            ];
        }

        $headers = $this->headers;
        $headers[$key] = [
            'name'   => $name,
            'values' => $value,
        ];

        return clone($this, [
            'headers' => $headers,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withoutHeader(string $name): MessageInterface
    {
        $key = \strtolower($name);

        if (!isset($this->headers[$key])) {
            return $this;
        }

        $headers = $this->headers;
        unset($headers[$key]);

        return clone($this, [
            'headers' => $headers,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            throw new \LogicException('Body has not been initialized');
        }
        return $this->body;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withBody(StreamInterface $body): MessageInterface
    {
        return clone($this, [
            'body' => $body,
        ]);
    }
}
