<?php

namespace MessagingSmartEvaluering;

use EmployeesSmartEvaluering\Http\Middleware\AuthDataScopes;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Illuminate\Support\ServiceProvider;
use MessagingSmartEvaluering\Commands\CreateMockFlowResponsesCommand;
use MessagingSmartEvaluering\Commands\CreateMockIterationResponsesCommand;
use MessagingSmartEvaluering\Commands\CreateMockMessagingFlows;
use MessagingSmartEvaluering\Commands\SendMessagingIterationMessagesCommand;
use MessagingSmartEvaluering\Commands\SendReadyMessagingFlowsIterationsCommand;
use MessagingSmartEvaluering\Contracts\MessagingFlowRepository;
use MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository;
use MessagingSmartEvaluering\Loggers\MessagingLoggerFactory;

class MessagingSmartEvalueringServiceProvider extends ServiceProvider
{
    public function boot(Request $request, Kernel $kernel)
    {
        $this->defineRoutes();
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->bindFilesPaths();
        }
//        $this->loadViewsFrom(realpath(__DIR__.'/../resources/views'), 'employees-se');

        $this->bindRepositories();
        !defined('_MESSAGING_ROOT_') && define('_MESSAGING_ROOT_', dirname(__DIR__, 1));

        $this->bindCommands();
        $this->bindLoggersDependencies();
    }


    /**
     * Define the routes.
     *
     * @param string|null $domain
     *
     * @return void
     */
    protected function defineRoutes($domain = null)
    {
        //TODO: remove web-to-messaging-se middleware after transmitting messaging to separate REST API
        Route::middleware('web')
            ->namespace('\MessagingSmartEvaluering\Http\Controllers')
            ->group(__DIR__ . '/../routes/routes.php');
    }

    protected function bindRepositories()
    {
        collect([
            MessagingSendingIterationRepository::class => \MessagingSmartEvaluering\Repositories\MessagingSendingIterationRepository::class,
            MessagingFlowRepository::class => \MessagingSmartEvaluering\Repositories\MessagingFlowRepository::class,
        ])->each(function ($implementation, $interface) {
            $this->app->bind($interface, $implementation);
        });

    }

    public function register()
    {
        app('router')->aliasMiddleware('employees-se-auth-data-scopes', AuthDataScopes::class);
    }

    private function bindFilesPaths() {
//        $this->publishes([realpath(__DIR__.'/../resources/views') => resource_path('views/vendor/employees-se')], 'employees-views');
//        $this->publishes([realpath(__DIR__.'/../resources/assets/js') => resource_path('assets/js/employees-se')], 'employees-js');
//        $this->publishes([realpath(__DIR__.'/../resources/assets/sass') => resource_path('assets/sass/employees-se')], 'employees-sass');
//        $this->publishes([
//            realpath(__DIR__.'/../resources/views')       => resource_path('views/vendor/employees-se'),
//            realpath(__DIR__.'/../resources/assets/js')   => resource_path('assets/js/employees-se'),
//            realpath(__DIR__.'/../resources/assets/sass') => resource_path('assets/less/employees-se'),
//        ], 'employees-full');
    }

    private function bindLoggersDependencies() {
        $this->app->singleton(MessagingLoggerFactory::class, function($app) {
            return new MessagingLoggerFactory();
        });
    }

    private function bindCommands() {
        $this->commands([
            CreateMockMessagingFlows::class,

            SendReadyMessagingFlowsIterationsCommand::class,
            SendMessagingIterationMessagesCommand::class,

            CreateMockFlowResponsesCommand::class,
            CreateMockIterationResponsesCommand::class,
        ]);
    }
}