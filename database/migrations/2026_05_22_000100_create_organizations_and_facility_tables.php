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
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('timezone', 64)->default('UTC');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('wards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('floor', 30)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ward_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('room_number', 40);
            $table->timestamps();

            $table->unique(['ward_id', 'room_number']);
        });

        Schema::create('beds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active')->index();
            $table->string('bed_number', 40);
            $table->timestamps();

            $table->unique(['room_id', 'bed_number']);
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('medical_record_no', 80);
            $table->string('full_name', 160)->index();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['organization_id', 'medical_record_no']);
        });

        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('serial_number', 120);
            $table->string('mqtt_topic', 255)->unique();
            $table->string('model', 80)->nullable();
            $table->string('firmware_version', 80)->nullable();
            $table->enum('status', ['online', 'offline', 'maintenance', 'retired'])->default('offline')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'serial_number']);
        });

        Schema::create('device_bed_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamp('mounted_at');
            $table->timestamp('unmounted_at')->nullable();
            $table->foreignId('mounted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['device_id', 'unmounted_at']);
            $table->index(['bed_id', 'unmounted_at']);
        });

        Schema::create('nurse_device_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nurse_user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['nurse_user_id', 'device_id']);
            $table->index(['organization_id', 'nurse_user_id']);
        });

        Schema::create('nurse_fcm_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nurse_user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('fcm_token', 255)->unique();
            $table->string('app_version', 40)->nullable();
            $table->string('device_os', 20)->nullable();
            $table->string('device_model', 80)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nurse_user_id', 'is_active']);
        });

        Schema::create('device_calibrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('tare_weight_grams', 10, 2);
            $table->decimal('scale_factor', 10, 6)->default(1);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('calibrated_at');
            $table->foreignId('calibrated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'calibrated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_calibrations');
        Schema::dropIfExists('nurse_fcm_tokens');
        Schema::dropIfExists('nurse_device_subscriptions');
        Schema::dropIfExists('device_bed_assignments');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('wards');
        Schema::dropIfExists('organizations');
    }
};
