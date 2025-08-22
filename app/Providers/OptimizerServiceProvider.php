<?php

namespace App\Providers;

use App\Services\AppOptimizer;
use Illuminate\Support\ServiceProvider;

class OptimizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AppOptimizer::class, function ($app) {
            $optimizer = new AppOptimizer();

            // Настройки из конфигурации
            $settings = config('optimizer', []);

            if (!empty($settings)) {
                $optimizer->setSettings($settings);
            }

            return $optimizer;
        });

        // Регистрация алиаса для более удобного использования
        $this->app->alias(AppOptimizer::class, 'optimizer');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикация конфигурационного файла
        $this->publishes([
            __DIR__.'/../../config/optimizer.php' => config_path('optimizer.php'),
        ], 'config');
    }
}
