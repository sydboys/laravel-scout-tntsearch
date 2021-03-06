<?php

namespace Vanry\Scout;

use InvalidArgumentException;
use Laravel\Scout\EngineManager;
use TeamTNT\TNTSearch\TNTSearch;
use Vanry\Scout\Console\ImportCommand;
use Illuminate\Support\ServiceProvider;
use Vanry\Scout\Engines\TNTSearchEngine;

class TNTSearchScoutServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(ImportCommand::class);

            $this->publishes([
                __DIR__.'/../config/tntsearch.php' => config_path('tntsearch.php'),
            ]);
        }

        $this->mergeConfigFrom(__DIR__.'/../config/tntsearch.php', 'tntsearch');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app[EngineManager::class]->extend('tntsearch', function ($app) {
            $tnt = new TNTSearch;

            $tnt->loadConfig($this->getConfig());
            $tnt->setDatabaseHandle(app('db')->connection()->getPdo());

            return new TNTSearchEngine($tnt);
        });
    }

    protected function getConfig()
    {
        $driver = config('database.default');

        $config = config('tntsearch') + config("database.connections.{$driver}");

        if (! array_key_exists($config['default'], $config['tokenizers'])) {
            throw new InvalidArgumentException("Tokenizer [{$config['default']}] is not defined.");
        }

        return array_merge($config, ['tokenizer' => $config['tokenizers'][$config['default']]['driver']]);
    }
}
