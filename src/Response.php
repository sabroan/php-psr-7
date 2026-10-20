<?php

declare(strict_types=1);

namespace Sabroan\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Sabroan\Http\Message\Response\HttpCode;

/**
 * @api
 */
final class Response extends AbstractMessage implements ResponseInterface
{
    private int $code = 0;
    private string $reason = '';

    /** {@inheritDoc} */
    #[\Override]
    public function getStatusCode(): int
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-capabilities read-props
     */
    #[\Override]
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        HttpCode::ensureValid($code);

        return clone($this, [
            'code'   => $code,
            'reason' => ('' === $reasonPhrase)
                ? HttpCode::getReasonPhrase($code)
                : $reasonPhrase,
        ]);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function getReasonPhrase(): string
    {
        return $this->reason;
    }
}
