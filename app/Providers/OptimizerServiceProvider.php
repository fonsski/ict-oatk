<?php

namespace App\Providers;

use App\Services\AppOptimizer;
use Illuminate\Support\ServiceProvider;

class OptimizerServiceProvider extends ServiceProvider
{
    
     * Register services.

    public function register(): void
    {
        $this->app->singleton(AppOptimizer::class, function ($app) {
            $optimizer = new AppOptimizer();

            
            $settings = config('optimizer', []);

            if (!empty($settings)) {
                $optimizer->setSettings($settings);
            }

            return $optimizer;
        });

        
        $this->app->alias(AppOptimizer::class, 'optimizer');
    }

    
     * Bootstrap services.

    public function boot(): void
    {
        
        $this->publishes([
            __DIR__.'/../../config/optimizer.php' => config_path('optimizer.php'),
        ], 'config');
    }
}
