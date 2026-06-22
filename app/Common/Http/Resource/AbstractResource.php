<?php

declare(strict_types=1);

namespace App\Common\Http\Resource;

abstract class AbstractResource
{
    protected function __construct(
        protected object|array $resource,
    ) {
    }

    public static function make(object|array $resource): static
    {
        return new static($resource);
    }

    abstract public function toArray(): array;

    public function toResponse(): array
    {
        return ['data' => $this->toArray()];
    }
}
