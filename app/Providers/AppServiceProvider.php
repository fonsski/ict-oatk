<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AppOptimizer;

class AppServiceProvider extends ServiceProvider
{
    
     * Register any application services.

    public function register(): void
    {
        
        $this->app->register(OptimizerServiceProvider::class);

        
        $this->app->register(GmailServiceProvider::class);

        
        $this->app->singleton(AppOptimizer::class, function ($app) {
            return new AppOptimizer();
        });

        
        $this->app->alias(AppOptimizer::class, "optimizer");
    }

    
     * Bootstrap any application services.

    public function boot(): void
    {
        
        if (method_exists($this->app["router"], "aliasMiddleware")) {
            $this->app["router"]->aliasMiddleware(
                "role",
                \App\Http\Middleware\CheckRole::class,
            );
            $this->app["router"]->aliasMiddleware(
                "require_role",
                \App\Http\Middleware\CheckRole::class,
            );
        }

        
        if (config("optimizer.compressResponses", true)) {
            $this->app->singleton(
                \Illuminate\Contracts\Routing\ResponseFactory::class,
                function ($app) {
                    return new \Illuminate\Routing\ResponseFactory(
                        $app[\Illuminate\Contracts\View\Factory::class],
                        $app[\Illuminate\Routing\Redirector::class],
                    );
                },
            );
        }
    }
}
