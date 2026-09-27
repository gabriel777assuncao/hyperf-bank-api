<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Model;

use App\Common\Infrastructure\Model;
use DateTimeInterface;

/**
 * @property string $id
 * @property string $aggregate_id
 * @property string $status
 * @property int $tries
 * @property DateTimeInterface|null $next_retry_at
 * @property array<string, mixed> $payload
 * @property DateTimeInterface|null $published_at
 * @property DateTimeInterface|null $created_at
 * @property DateTimeInterface|null $updated_at
 */
class OutboxEventModel extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'outbox_events';

    /** @var array<string> */
    protected array $fillable = ['id', 'aggregate_id', 'status', 'tries', 'next_retry_at', 'payload', 'published_at'];

    /** @var array<string, string> */
    protected array $casts = [
        'tries' => 'integer',
        'next_retry_at' => 'datetime',
        'payload' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
