<?php

declare(strict_types=1);

namespace App\Common\Infrastructure\Http\Resource;

/**
 * @phpstan-consistent-constructor
 */
abstract class AbstractResource
{
    /**
     * @param object|array<string, mixed> $resource
     */
    protected function __construct(
        protected object|array $resource,
    ) {
    }

    /**
     * @param object|array<string, mixed> $resource
     */
    public static function make(object|array $resource): static
    {
        return new static($resource);
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function toResponse(): array
    {
        return ['data' => $this->toArray()];
    }
}
