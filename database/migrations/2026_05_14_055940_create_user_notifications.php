<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('batch_id')->nullable();
            $table->enum('channel', ['sms', 'email', 'push']);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('status', ['accepted', 'pending', 'delivered', 'failed', 'canceled'])->default('accepted');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->timestamps();

            $table->index(['channel']);
            $table->index(['created_at']);
            $table->index(['status']);
            $table->index(['batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
