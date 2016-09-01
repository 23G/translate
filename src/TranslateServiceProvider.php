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

        $router->pushMiddlewareToGroup('web', \DylanLamers\Translate\Middleware\TranslateInitiator::class);

        $events->listen('locale.changed', function () {
            app('translate')->localeChanged();
        });

        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes.php';
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translate.php', 'translate');

        $this->commands([
            'DylanLamers\Translate\Console\Commands\Install',
            'DylanLamers\Translate\Console\Commands\AddLanguage'
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
}
