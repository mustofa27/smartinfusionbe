<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->enum('role', ['admin', 'super-admin', 'nurse'])->default('nurse')->after('password');
            $table->string('phone', 30)->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index('role');
            $table->index('is_active');
            $table->unique(['organization_id', 'email']);
        });

        // Backfill role on existing rows for compatibility in existing environments.
        DB::table('users')->whereNull('role')->update(['role' => 'nurse']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_organization_id_email_unique');
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'role', 'phone', 'is_active', 'last_login_at']);
        });
    }
};
