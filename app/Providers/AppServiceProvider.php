<?php

namespace App\Providers;

use App\Console\Commands\RpcConsumeCommand;
use App\Socialite\PassportProvider;
use App\Worker\RpcConsumer;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\UrlGenerator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if(env('FORCE_HTTPS')) {
            $this->app['request']->server->set('HTTPS', true);
        }

        if ($this->app->runningInConsole()) {
            $this->app->singleton('rabbitmq.rpc-consumer', function () {
                $isDownForMaintenance = function () {
                    return $this->app->isDownForMaintenance();
                };

                return new RpcConsumer(
                    $this->app['queue'],
                    $this->app['events'],
                    $this->app[ExceptionHandler::class],
                    $isDownForMaintenance
                );
            });

            $this->app->singleton(RpcConsumeCommand::class, static function ($app) {
                return new RpcConsumeCommand(
                    $app['rabbitmq.rpc-consumer'],
                    $app['cache.store']
                );
            });

            $this->commands([
                RpcConsumeCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(UrlGenerator $url)
    {
        if(env('FORCE_HTTPS')) {
            $url->forceScheme('https');
        }

        Socialite::extend('passport', function ($app) {
            $config = $app['config']['services.passport'];

            return new PassportProvider(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                URL::to($config['redirect'])
            );
        });
    }
}
