<?php

namespace Devdot\LogArtisan;

use Illuminate\Foundation\Console\AboutCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        AboutCommand::add('devdot/log-user', fn () => [
            'available' => true,
        ]);

        // install all our commands
        if($this->app->runningInConsole()) {
            $this->commands([
                Commands\AboutLog::class,
                Commands\ClearLog::class,
                Commands\ShowLog::class,
                Commands\SearchLog::class,
            ]);
        }
    }
}
