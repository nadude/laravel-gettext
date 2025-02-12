<?php

namespace Xinax\LaravelGettext;

use Illuminate\Support\ServiceProvider;

/**
 * Main service provider
 *
 * Class LaravelGettextServiceProvider
 * @package Xinax\LaravelGettext
 */
class LaravelGettextServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('laravel-gettext.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configuration = Config\ConfigManager::create();

        $this->app->bind(
            'Adapters/AdapterInterface',
            $configuration->get()->getAdapter()
        );

        // Main class register
        $this->app->singleton('laravel-gettext', function ($app) use ($configuration) {
            $fileSystem = new FileSystem($configuration->get(), app_path(), storage_path());

            $translator = $configuration->get()->getHandler() === 'symfony'
                ? new Translators\Symfony(
                    $configuration->get(),
                    new Adapters\LaravelAdapter,
                    $fileSystem
                )
                : new Translators\Gettext(
                    $configuration->get(),
                    new Adapters\LaravelAdapter,
                    $fileSystem
                );

            return new LaravelGettext($translator);
        });

        include_once __DIR__ . '/Support/helpers.php';

        // Alias
        $this->app->alias('laravel-gettext', LaravelGettext::class);

        $this->registerCommands();
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        // Package commands
        $this->app->singleton('xinax::gettext.create', function ($app) {
            return new Commands\GettextCreate();
        });

        $this->app->singleton('xinax::gettext.update', function ($app) {
            return new Commands\GettextUpdate();
        });

        $this->commands([
            'xinax::gettext.create',
            'xinax::gettext.update',
        ]);
    }

    /**
     * Get the services.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'laravel-gettext',
        ];
    }
}
