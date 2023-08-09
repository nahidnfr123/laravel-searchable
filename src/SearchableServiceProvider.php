<?php

namespace Nahid\Searchable;

use Illuminate\Support\ServiceProvider;

class SearchableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/searchable.php', 'searchable',);

        $this->app->bind('searchable', function ($app) {
            return new \Nahid\Searchable\Facades\Searchable();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/searchable.php' => config_path('searchable.php'),
        ], 'config');
    }
}
