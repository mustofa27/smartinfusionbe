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
        Schema::create('nurse_device_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nurse_user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'nurse_user_id', 'device_id'], 'nds_org_user_device_unique');
            $table->index(['organization_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurse_device_subscriptions');
    }
};