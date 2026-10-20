<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Message;

use Sabroan\Http\Message\Rfc\Rfc9110;

/**
 * @psalm-immutable
 */
final class Header
{
    /**
     * @psalm-pure
     */
    // @phpstan-ignore pureMethod.void
    public static function validateName(string $name): void
    {
        // @phpstan-ignore possiblyImpure.functionCall
        if (! (bool) \preg_match(Rfc9110::TOKEN_REGEXP, $name)) {
            throw new \InvalidArgumentException(
                "Header name '{$name}' not allowed by RFC 9110",
            );
        }
    }

    /**
     * @psalm-pure
     */
    public static function toValue(string $value): string
    {
        $value = \trim($value, " \t");

        // @phpstan-ignore possiblyImpure.functionCall
        if (! (bool) \preg_match(Rfc9110::VALUE_REGEXP, $value)) {
            throw new \InvalidArgumentException(
                "Header value '{$value}' not allowed by RFC 9110",
            );
        }

        return $value;
    }

    /**
     * @psalm-pure
     *
     * @param array<string> $values
     * @return list<string>
     */
    public static function toValues(array $values): array
    {
        $values = \array_values($values);

        foreach ($values as $key => $value) {
            $values[$key] = self::toValue($value);
        }

        return $values;
    }

    /**
     * @psalm-pure
     *
     * @param string|array<string> $values
     * @return array<string>
     */
    public static function toArray(string|array $values): array
    {
        return \is_array($values) ? $values : [$values];
    }
}
