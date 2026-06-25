<?php

declare(strict_types=1);

use App\User\Domain\Enum\UserType;
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
            $table->string('cpf', 14)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('type', 20)->default(UserType::NORMAL->value);
            $table->datetime('created_at', 6);
            $table->datetime('updated_at', 6);

            $table->unique('cpf');
            $table->unique('cnpj');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
