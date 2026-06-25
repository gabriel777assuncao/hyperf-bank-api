<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payer_id');
            $table->uuid('payee_id');
            $table->bigInteger('value')->unsigned();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->datetime('created_at', 6);
            $table->datetime('updated_at', 6);

            $table->foreign('payer_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('payee_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('payer_id');
            $table->index('payee_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
