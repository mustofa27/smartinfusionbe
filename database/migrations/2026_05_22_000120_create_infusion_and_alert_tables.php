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
        Schema::create('infusion_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('bed_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fluid_name', 120);
            $table->decimal('bag_volume_ml', 8, 2);
            $table->decimal('bag_empty_weight_grams', 10, 2);
            $table->decimal('initial_weight_grams', 10, 2);
            $table->decimal('fluid_density_g_per_ml', 6, 4)->default(1.0000);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'interrupted'])->default('active');
            $table->text('notes')->nullable();
            $table->decimal('last_weight_grams', 10, 2)->nullable();
            $table->decimal('last_remaining_ml', 10, 2)->nullable();
            $table->decimal('last_flow_ml_per_hour', 10, 2)->nullable();
            $table->timestamp('last_reading_at')->nullable();
            $table->string('patient_name_snapshot', 160)->nullable();
            $table->string('mrn_snapshot', 80)->nullable();
            $table->string('bed_label_snapshot', 120)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['device_id', 'status']);
            $table->index(['patient_id', 'started_at']);
            $table->index('last_reading_at');
        });

        Schema::create('infusion_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('infusion_session_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('measured_weight_grams', 10, 2);
            $table->decimal('remaining_ml', 10, 2);
            $table->decimal('flow_ml_per_hour', 10, 2)->nullable();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->string('signal_quality', 40)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('received_at');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['infusion_session_id', 'recorded_at']);
            $table->index(['device_id', 'recorded_at']);
            $table->index(['organization_id', 'recorded_at']);
        });

        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('code', 50);
            $table->decimal('threshold_value', 12, 4);
            $table->string('threshold_unit', 30);
            $table->unsignedInteger('cooldown_seconds')->default(300);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('infusion_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('alert_type', 50);
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->string('message', 255);
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
            $table->string('dedupe_key', 120)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'triggered_at']);
            $table->index(['device_id', 'status']);
            $table->index('dedupe_key');
        });

        Schema::create('alert_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('alert_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('channel', ['fcm'])->default('fcm');
            $table->string('fcm_token', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('delivery_status', ['queued', 'sent', 'failed'])->default('queued');
            $table->string('provider_message_id', 190)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamps();

            $table->index(['alert_id', 'delivery_status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at');

            $table->index(['organization_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alert_deliveries');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('infusion_readings');
        Schema::dropIfExists('infusion_sessions');
    }
};
