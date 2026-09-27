<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\{Blueprint, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aggregate_id');
            $table->string('status')->default('pending');
            $table->unsignedInteger('tries')->default(0);
            $table->datetime('next_retry_at', 6)->nullable();
            $table->json('payload');
            $table->datetime('published_at', 6)->nullable();
            $table->datetime('created_at', 6);
            $table->datetime('updated_at', 6);

            $table->foreign('aggregate_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('restrict');

            $table->index('aggregate_id');
            $table->index(['status', 'created_at']);
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
