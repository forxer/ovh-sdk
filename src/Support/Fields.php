<?php

declare(strict_types=1);

namespace OvhSdk\Support;

use UnexpectedValueException;

/**
 * Typed reads of a decoded OVHcloud JSON object, for the DTO factories.
 *
 * A required field that is missing or of the wrong type, or a nullable field of the wrong type, means the
 * response does not follow the schema: an UnexpectedValueException is thrown rather than hydrating half a DTO.
 *
 * @internal
 */
final class Fields
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return \is_string($value) ? $value : throw self::invalid($key, 'a string');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return $value === null || \is_string($value) ? $value : throw self::invalid($key, 'a string or null');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        return \is_int($value) ? $value : throw self::invalid($key, 'an integer');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return $value === null || \is_int($value) ? $value : throw self::invalid($key, 'an integer or null');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function bool(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;

        return \is_bool($value) ? $value : throw self::invalid($key, 'a boolean');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function nullableBool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return $value === null || \is_bool($value) ? $value : throw self::invalid($key, 'a boolean or null');
    }

    /**
     * Raw value of an enum field, for fromOvh(). Never throws: anything but a string resolves to the Unknown case.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function enumValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>|null
     */
    public static function nullableObject(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        return $value === null || \is_array($value) ? $value : throw self::invalid($key, 'an object or null');
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<array<array-key, mixed>>
     */
    public static function objectList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! \is_array($value) || ! array_is_list($value)) {
            throw self::invalid($key, 'a list of objects');
        }

        $objects = [];

        foreach ($value as $item) {
            $objects[] = \is_array($item) ? $item : throw self::invalid($key, 'a list of objects');
        }

        return $objects;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    public static function stringList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! \is_array($value) || ! array_is_list($value)) {
            throw self::invalid($key, 'a list of strings');
        }

        $strings = [];

        foreach ($value as $item) {
            $strings[] = \is_string($item) ? $item : throw self::invalid($key, 'a list of strings');
        }

        return $strings;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<int>|null
     */
    public static function nullableIntList(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! \is_array($value) || ! array_is_list($value)) {
            throw self::invalid($key, 'a list of integers or null');
        }

        $integers = [];

        foreach ($value as $item) {
            $integers[] = \is_int($item) ? $item : throw self::invalid($key, 'a list of integers or null');
        }

        return $integers;
    }

    private static function invalid(string $key, string $expected): UnexpectedValueException
    {
        return new UnexpectedValueException(\sprintf('OVH response field "%s" must be %s.', $key, $expected));
    }
}
