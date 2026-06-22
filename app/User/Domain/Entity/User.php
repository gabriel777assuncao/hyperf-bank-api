<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Enum\UserType;
use App\User\Domain\ValueObject\{Cnpj, Cpf, Email, Password};
use DateTimeImmutable;

final class User
{
    public function __construct(
        private string $id,
        private string $fullName,
        private Email $email,
        private Password $password,
        private UserType $type,
        private ?Cpf $cpf = null,
        private ?Cnpj $cnpj = null,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function cpf(): ?Cpf
    {
        return $this->cpf;
    }

    public function cnpj(): ?Cnpj
    {
        return $this->cnpj;
    }

    public function document(): string
    {
        if ($this->cpf !== null) {
            return $this->cpf->formatted();
        }

        return $this->cnpj->formatted();
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): Password
    {
        return $this->password;
    }

    public function type(): UserType
    {
        return $this->type;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isShopkeeper(): bool
    {
        return $this->type === UserType::SHOPKEEPER;
    }

    public function isNormal(): bool
    {
        return $this->type === UserType::NORMAL;
    }
}
