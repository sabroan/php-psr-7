<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\UriInterface;
use Sabroan\Http\Message\Rfc\IanaSchemePort;
use Sabroan\Http\Message\Uri\EncodeEnum;
use Sabroan\Http\Message\Uri\Port;
use Sabroan\Http\Message\Uri\Scheme;
use Uri\Rfc3986\Uri as PHPUri;

/**
 * @api
 */
final class Uri implements UriInterface, \Stringable
{
    private readonly PHPUri $uri;

    public function __construct()
    {
        $this->uri = new PHPUri('');
    }

    /**
     * @psalm-capabilities read-props
     */
    private function clone(PHPUri $uri): static
    {
        return clone($this, [
            'uri' => $uri,
        ]);
    }

    /**
     * @psalm-pure
     */
    private static function toPath(string $path, bool $hasAuthority): string
    {
        if ($hasAuthority && '' !== $path && '/' !== $path[0]) {
            return '/' . $path;
        }

        if (!$hasAuthority && \str_starts_with($path, '//')) {
            return '/' . \ltrim($path, '/');
        }

        return $path;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getScheme(): string
    {
        return $this->uri->getScheme() ?? '';
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getAuthority(): string
    {
        $authority = $this->getHost();

        $user = $this->getUserInfo();
        if ('' !== $user) {
            $authority = "{$user}@{$authority}";
        }

        $port = $this->getPort();
        if (null !== $port) {
            $authority = "{$authority}:{$port}";
        }

        return $authority;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getUserInfo(): string
    {
        return $this->uri->getUserInfo() ?? '';
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getHost(): string
    {
        return $this->uri->getHost() ?? '';
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getPort(): ?int
    {
        $port = $this->uri->getPort();
        if (null === $port) {
            return null;
        }

        $scheme = $this->uri->getScheme() ?? '';
        if ('' === $scheme) {
            return $port;
        }

        $default = IanaSchemePort::DEFAULT[$scheme] ?? null;
        if ($default === $port) {
            return null;
        }

        return $port;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getPath(): string
    {
        $path = $this->uri->getRawPath();

        if (\str_starts_with($path, '//')) {
            return '/' . \ltrim($path, '/');
        }

        return $path;
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getQuery(): string
    {
        return $this->uri->getQuery() ?? '';
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getFragment(): string
    {
        return $this->uri->getFragment() ?? '';
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withScheme(string $scheme): static
    {
        if ($scheme === $this->uri->getScheme()) {
            return $this;
        }
        if ('' === $scheme) {
            $scheme = null;
        } else {
            $scheme = \strtolower($scheme);
            Scheme::ensureValid($scheme);
        }
        try {
            return clone($this, [
                'uri' => $this->uri->withScheme($scheme),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withUserInfo(
        #[\SensitiveParameter]
        string $user,
        #[\SensitiveParameter]
        ?string $password = null,
    ): static {
        if ('' === $user) {
            return $this->clone(
                $this->uri->withUserInfo(null),
            );
        }
        $encoder = EncodeEnum::USERINFO;
        $user = $encoder->encode($user);
        if (null !== $password && '' !== $password) {
            $password = $encoder->encode($password);
            $user .= ':' . $password;
        }

        if ($user === $this->uri->getUserInfo()) {
            return $this;
        }

        $uri = (null === $this->uri->getHost())
            ? $this->uri->withHost('')
            : $this->uri;

        try {
            return clone($this, [
                'uri' => $uri->withUserInfo($user),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withHost(string $host): static
    {
        if ($host === $this->uri->getHost()) {
            return $this;
        }

        $host = ('' === $host) ? null : \strtolower($host);
        $path = self::toPath($this->uri->getPath(), null !== $host);

        try {
            return clone($this, [
                'uri' => $this->uri
                    ->withPath($path)
                    ->withHost($host),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withPort(?int $port): static
    {
        if ($port === $this->uri->getPort()) {
            return $this;
        }

        if (null !== $port) {
            Port::ensureValid($port);
        }

        $uri = (null !== $port && null === $this->uri->getHost())
            ? $this->uri->withHost('')
            : $this->uri;

        try {
            return clone($this, [
                'uri' => $uri->withPort($port),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withPath(string $path): static
    {
        if ($path === $this->uri->getRawPath()) {
            return $this;
        }

        if ('' !== $path) {
            $path = EncodeEnum::PATH->encode($path);
        }
        $path =  self::toPath($path, '' !== $this->getAuthority());

        try {
            return clone($this, [
                'uri' => $this->uri->withPath($path),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withQuery(string $query): static
    {
        if ($query === $this->uri->getQuery()) {
            return $this;
        }

        if (\str_starts_with($query, '?')) {
            throw new \InvalidArgumentException('Query must not start with "?"');
        }

        $query = ('' === $query)
            ? null
            : EncodeEnum::QUERY->encode($query);

        try {
            return clone($this, [
                'uri' => $this->uri->withQuery($query),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function withFragment(string $fragment): static
    {
        if ($fragment === $this->uri->getFragment()) {
            return $this;
        }

        if (\str_starts_with($fragment, '#')) {
            throw new \InvalidArgumentException('Fragment must not start with "#"');
        }

        $fragment = ('' === $fragment)
            ? null
            : EncodeEnum::QUERY->encode($fragment);

        try {
            return clone($this, [
                'uri' => $this->uri->withFragment($fragment),
            ]);
        } catch (\Uri\InvalidUriException $exception) {
            throw new \InvalidArgumentException(previous: $exception);
        }
    }

    /** {@inheritDoc} */
    #[\Override]
    public function __toString(): string
    {
        $scheme    = $this->getScheme();
        $authority = $this->getAuthority();
        $path      = $this->uri->getRawPath();
        $query     = $this->getQuery();
        $fragment  = $this->getFragment();

        if ('' !== $scheme) {
            $scheme .= ':';
        }
        if ('' !== $authority) {
            $authority = '//' . $authority;
        }
        if ('' !== $query) {
            $query = '?' . $query;
        }
        if ('' !== $fragment) {
            $fragment = '#' . $fragment;
        }

        return $scheme . $authority . $path . $query . $fragment;
    }
}
