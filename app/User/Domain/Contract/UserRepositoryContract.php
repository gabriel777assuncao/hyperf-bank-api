<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

use App\User\Domain\Entity\User;

interface UserRepositoryContract
{
    public function findById(string $id): ?User;

    public function findByDocument(string $document): ?User;

    public function findByCpf(string $cpf): ?User;

    public function findByCnpj(string $cnpj): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;
}
