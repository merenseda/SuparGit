<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('developer')->after('email'); // admin, developer, viewer
            $table->text('github_token')->nullable()->after('role');
            $table->string('github_username')->nullable()->after('github_token');
            $table->string('github_avatar')->nullable()->after('github_username');
            $table->text('fcm_token')->nullable()->after('github_avatar');
            $table->boolean('notifications_enabled')->default(true)->after('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'github_token', 'github_username', 'github_avatar', 'fcm_token', 'notifications_enabled']);
        });
    }
};
