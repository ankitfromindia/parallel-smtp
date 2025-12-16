<?php

namespace AnkitFromIndia\ParallelSmtp;

use Illuminate\Support\ServiceProvider;
use AnkitFromIndia\ParallelSmtp\Http\ParallelSmtpClient;

class ParallelSmtpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/parallel-smtp.php', 'parallel-smtp');
        
        $this->app->singleton(ParallelSmtpClient::class, function ($app) {
            return new ParallelSmtpClient(
                config('parallel-smtp.smtp'),
                config('parallel-smtp.max_connections', 10),
                config('parallel-smtp.messages_per_connection', 100)
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/parallel-smtp.php' => config_path('parallel-smtp.php'),
        ], 'parallel-smtp-config');
    }
}
