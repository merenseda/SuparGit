<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('repo_name');
            $table->string('repo_full_name');
            $table->string('branch')->default('main');
            $table->string('file_path');
            $table->string('original_filename');
            $table->text('commit_message');
            $table->string('commit_sha')->nullable();
            $table->string('diff_url')->nullable();
            $table->string('file_url')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_logs');
    }
};
