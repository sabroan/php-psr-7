<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @api
 */
final class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<array-key, mixed> */
    private array $cookies = [];

    /** @var array<array-key, mixed> */
    private array $files = [];

    /** @var array<array-key, mixed> */
    private array $query = [];

    /** @var null|object|array<mixed> */
    private array|object|null $post = null;

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, mixed> $server
     *
     * @psalm-capabilities read-props
     */
    public function __construct(
        private readonly array $server,
    ) {}

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, mixed> $cookies
     *
     * @psalm-capabilities read-props
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    #[\Override]
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        if ($cookies === $this->getCookieParams()) {
            return $this;
        }
        return clone($this, [
            'cookies' => $cookies,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, mixed> $query
     *
     * @psalm-capabilities read-props
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    #[\Override]
    public function withQueryParams(array $query): ServerRequestInterface
    {
        if ($query === $this->getQueryParams()) {
            return $this;
        }
        return clone($this, [
            'query' => $query,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @inheritDoc }
     *
     * @param array<array-key, mixed> $uploadedFiles
     *
     * @psalm-capabilities read-props
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    #[\Override]
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        if ($uploadedFiles === $this->getUploadedFiles()) {
            return $this;
        }

        $stack = [$uploadedFiles];
        while ($stack) {
            $current = \array_pop($stack);

            /** @var mixed $uploadedFile */
            foreach ($current as $uploadedFile) {

                if ($uploadedFile instanceof UploadedFileInterface) {
                    continue;
                }

                if (\is_array($uploadedFile)) {
                    $stack[] = $uploadedFile;
                    continue;
                }

                throw new \InvalidArgumentException(
                    'File must implement ' . UploadedFileInterface::class,
                );
            }
        }

        return clone($this, [
            'files' => $uploadedFiles,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return null|object|array<mixed>
     */
    #[\Override]
    public function getParsedBody(): array|object|null
    {
        return $this->post;
    }

    /**
     * {@inheritDoc}
     *
     * @param null|object|array<mixed> $data
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withParsedBody(mixed $data): ServerRequestInterface
    {
        if ($data === $this->getParsedBody()) {
            return $this;
        }

        /** @psalm-suppress DocblockTypeContradiction */
        // @phpstan-ignore function.alreadyNarrowedType, booleanAnd.alwaysFalse
        if (null !== $data && !\is_array($data) && !\is_object($data)) {
            throw new \InvalidArgumentException(
                'Parsed body must be a null, an array, or an object',
            );
        }

        return clone($this, [
            'post' => $data,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withAttribute(string $name, mixed $value): ServerRequestInterface
    {
        $attributes = $this->getAttributes();

        /** @psalm-suppress MixedAssignment */
        $attributes[$name] = $value;

        return clone($this, [
            'attributes' => $attributes,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        if (!isset($this->attributes[$name])) {
            return $this;
        }

        $attributes = $this->getAttributes();
        unset($attributes[$name]);
        return clone($this, [
            'attributes' => $attributes,
        ]);
    }
}
