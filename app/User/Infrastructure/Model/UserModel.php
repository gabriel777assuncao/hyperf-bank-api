<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Model;

use App\Common\Infrastructure\Model;
use DateTimeInterface;

/**
 * @property string $id
 * @property string $full_name
 * @property string|null $cpf
 * @property string|null $cnpj
 * @property string $email
 * @property string $password
 * @property string $type
 * @property DateTimeInterface|null $created_at
 * @property DateTimeInterface|null $updated_at
 */
class UserModel extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'users';

    /** @var array<string> */
    protected array $fillable = [
        'id',
        'full_name',
        'cpf',
        'cnpj',
        'email',
        'password',
        'type',
    ];

    /** @var array<string> */
    protected array $hidden = [
        'password',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
