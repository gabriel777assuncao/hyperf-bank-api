<?php

declare(strict_types=1);

namespace App\Shared\Model;

class Transaction extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'transactions';

    /** @var array<string> */
    protected array $fillable = ['id', 'payer_id', 'payee_id', 'value', 'status'];

    /** @var array<string, string> */
    protected array $casts = [
        'value' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
