<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Sabroan\Http\Message\Request\Method;
use Sabroan\Http\Message\Rfc\Rfc9112;

/**
 * @api
 */
class Request extends AbstractMessage implements RequestInterface
{
    private string $method = '';
    private string $target = '';
    private ?UriInterface $uri = null;

    /** {@inheritDoc} */
    #[\Override]
    public function getRequestTarget(): string
    {
        if ('' !== $this->target) {
            return $this->target;
        }

        if (null === $this->uri) {
            return '/';
        }

        $target     = '/' . \ltrim($this->uri->getPath(), '/');
        $query      = $this->uri->getQuery();

        if ('' !== $query) {
            $target .= '?' . $query;
        }

        return $target;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if (! (bool) \preg_match(Rfc9112::REQUEST_TARGET_REGEXP, $requestTarget)) {
            throw new \InvalidArgumentException("Request target '{$requestTarget}' not allowed by RFC 9112");
        }

        if ($requestTarget === $this->target) {
            return $this;
        }

        return clone($this, [
            'target' => $requestTarget,
        ]);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withMethod(string $method): RequestInterface
    {
        Method::ensureValid($method);

        if ($method === $this->getMethod()) {
            return $this;
        }

        return clone($this, [
            'method' => $method,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function getUri(): UriInterface
    {
        if (null === $this->uri) {
            throw new \LogicException('URI has not been initialized');
        }

        return $this->uri;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType
     */
    #[\Override]
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        if (isset($this->uri) && $uri === $this->uri) {
            return $this;
        }

        $new = clone($this, [
            'uri' => $uri,
        ]);

        if ($preserveHost && '' !== $this->getHeaderLine('Host')) {
            return $new;
        }

        $host = $uri->getHost();

        if ('' === $host) {
            return $new;
        }

        $port = $uri->getPort();

        if (null !== $port) {
            $host .= ':' . $port;
        }

        return $new->withHeader('Host', $host);
    }
}
