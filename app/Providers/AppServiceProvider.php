<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        try {
            if (Schema::hasTable('users') && !Schema::hasColumn('users', 'allowed_branches')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('allowed_branches')->nullable()->default('*')->after('role');
                });
            }
        } catch (\Throwable $e) {
            // Sessizce devam et
        }

        Vite::prefetch(concurrency: 3);
    }
}
