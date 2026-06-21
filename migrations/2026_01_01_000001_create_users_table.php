<?php

declare(strict_types=1);

use Domain\Enum\UserType;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 255);
            $table->string('cpf', 14);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('type', 20)->default(UserType::COMMON->value);
            $table->datetime('created_at', 6);
            $table->datetime('updated_at', 6);

            $table->unique('cpf');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
