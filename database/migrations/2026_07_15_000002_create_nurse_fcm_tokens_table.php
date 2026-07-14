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
        Schema::create('nurse_fcm_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nurse_user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('fcm_token', 255);
            $table->string('app_version', 40)->nullable();
            $table->string('device_os', 20)->nullable();
            $table->string('device_model', 80)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('fcm_token');
            $table->index(['organization_id', 'is_active']);
            $table->index(['nurse_user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurse_fcm_tokens');
    }
};