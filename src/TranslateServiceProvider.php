<?php

namespace DylanLamers\Translate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use DylanLamers\Translate\Models\Language;
use DylanLamers\Translate\Translate;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class TranslateServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot(Router $router, DispatcherContract $events)
    {
        $this->publishes([
            __DIR__.'/../config/translate.php' => config_path('translate.php'),
        ]);

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'migrations');
        
        if (config('translate.use_routes')) {
            $this->setupRoutes($this->app->router);
        }

        $router->pushMiddlewareToGroup('web', \DylanLamers\Translate\Middleware\TranslateInitiator::class);

        $events->listen('locale.changed', function () {
            app('translate')->localeChanged();
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translate.php', 'translate');

        $this->commands([
            'DylanLamers\Translate\Console\Commands\Install'
        ]);

        /*
            We need the constructor to be fired therefor we use app->instance.
        */
        
        $this->app->singleton('translate', function ($app) {
            return new Translate($app['session'], $app);
        });

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Translate', \DylanLamers\Translate\Facades\Translate::class);
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        $router->group(['namespace' => 'DylanLamers\Translate\Http\Controllers'], function ($router) {
            require __DIR__.'/Http/routes.php';
        });
    }
}
