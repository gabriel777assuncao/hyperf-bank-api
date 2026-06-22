<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Model;

use App\Common\Infrastructure\Model;

class WalletModel extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'wallets';

    /** @var array<string> */
    protected array $fillable = ['id', 'user_id', 'balance'];

    /** @var array<string, string> */
    protected array $casts = [
        'balance' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
