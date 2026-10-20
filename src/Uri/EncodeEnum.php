<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Uri;

use Sabroan\Http\Message\Rfc\Rfc3986;

enum EncodeEnum: string
{
    /** @link https://datatracker.ietf.org/doc/html/rfc3986#section-3.2.1 */
    case USERINFO   = '#(?:[^' . Rfc3986::UNRESERVED . Rfc3986::SUB_DELIMS . '%]++)|%(?!' . Rfc3986::HEXADECIMAL . ')#iu';

    /** @link https://datatracker.ietf.org/doc/html/rfc3986#section-3.3 */
    case PATH       = '#(?:[^' . Rfc3986::UNRESERVED . Rfc3986::SUB_DELIMS . ':@/%]++)|%(?!' . Rfc3986::HEXADECIMAL . ')#iu';

    /**
     * @link https://datatracker.ietf.org/doc/html/rfc3986#section-3.4
     * @link https://datatracker.ietf.org/doc/html/rfc3986#section-3.5
     **/
    case QUERY      = '#(?:[^' . Rfc3986::UNRESERVED . Rfc3986::SUB_DELIMS . ':@/?%]++)|%(?!' . Rfc3986::HEXADECIMAL . ')#iu';

    public function encode(
        #[\SensitiveParameter]
        string $value,
    ): string {
        $value = \preg_replace_callback(
            $this->value,
            static fn(array $match): string => \rawurlencode($match[0]),
            $value,
        );

        if (null === $value) {
            throw new \RuntimeException('Failed to encode URI component: ' . \preg_last_error_msg());
        }

        /** @var string $value */
        return $value;
    }
}
