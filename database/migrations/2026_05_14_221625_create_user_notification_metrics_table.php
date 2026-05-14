<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_metrics', function (Blueprint $table) {
            $table->foreignId('user_notification_id')
                ->primary()
                ->constrained('user_notifications')
                ->cascadeOnDelete();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->index('queued_at');
            $table->index('delivered_at');
            $table->index('failed_at');
            $table->index('canceled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_metrics');
    }
};
