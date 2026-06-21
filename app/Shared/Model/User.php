<?php

declare(strict_types=1);

namespace App\Shared\Model;

class User extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'users';

    /** @var array<string> */
    protected array $fillable = ['id', 'full_name', 'cpf', 'email', 'password', 'type'];

    /** @var array<string> */
    protected array $hidden = ['password'];

    /** @var array<string, string> */
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
