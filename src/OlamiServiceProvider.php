<?php

namespace Recca0120\Olami;

use Illuminate\Support\ServiceProvider;

class OlamiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config'];

            $client = new Client($config['services.olami.key'], $config['services.olami.secret']);

            if (empty($config['services.olami.endpoint']) === false) {
                $client->setEndpoint($config['services.olami.endpoint']);
            }

            return $client;
        });
    }
}
