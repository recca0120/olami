<?php

namespace Recca0120\Olami;

use Illuminate\Support\ServiceProvider;

class OlamiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client($app['config']['services.olami.key'], $app['config']['services.olami.secret']);
        });
    }
}
