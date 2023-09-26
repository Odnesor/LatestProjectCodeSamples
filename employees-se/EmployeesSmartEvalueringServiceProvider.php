<?php

namespace EmployeesSmartEvaluering;

use App\Application;
use EmployeesSmartEvaluering\Commands\CreateMockEmployees;
use EmployeesSmartEvaluering\Contracts\Employee\EmployeeRegisterRepository;
use EmployeesSmartEvaluering\Contracts\Employee\Filters\MessagingFlowFiltersRepository;
use EmployeesSmartEvaluering\Contracts\EmployeeAttributes\AttributeAddRepository;
use EmployeesSmartEvaluering\Contracts\EmployeeAttributes\AttributesGroupRegisterRepository;
use EmployeesSmartEvaluering\Contracts\MessagingIterationResponsesRepository;
use EmployeesSmartEvaluering\Http\Middleware\AuthDataScopes;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MessagingSmartEvaluering\Commands\CreateMockFlowResponsesCommand;
use MessagingSmartEvaluering\Commands\CreateMockIterationResponsesCommand;
use MessagingSmartEvaluering\Contracts\SmartEvalueringSenderContract;
use MessagingSmartEvaluering\Services\SenderAuthAdapter;
use SmartEvaluering\Models\Role;

class EmployeesSmartEvalueringServiceProvider extends ServiceProvider
{
    public function boot(Request $request, Kernel $kernel)
    {
        $this->defineRoutes();
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->bindFilesPaths();
        }
        $this->loadViewsFrom(realpath(__DIR__.'/../resources/views'), 'employees-se');

        $this->bindServiceContainers($request);
        $this->bindMessagesSender($request);
        !defined('_EMPLOYEES_ROOT_') && define('_EMPLOYEES_ROOT_', dirname(__DIR__, 1));

        $this->bindCommands();
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
        Route::middleware('web')
            ->namespace('\EmployeesSmartEvaluering\Http\Controllers')
            ->group(__DIR__ . '/../routes/routes.php');
    }

    protected function bindServiceContainers(Request $request)
    {
        collect([
            EmployeeRegisterRepository::class            => \EmployeesSmartEvaluering\Repositories\Employee\EmployeeRegisterRepository::class,
            AttributeAddRepository::class                => \EmployeesSmartEvaluering\Repositories\AttributeAddModelRepository::class,
            MessagingFlowFiltersRepository::class        => \EmployeesSmartEvaluering\Repositories\Employee\MessagingFlowFiltersRepository::class,
            AttributesGroupRegisterRepository::class => \EmployeesSmartEvaluering\Repositories\AttributesGroupRegisterModelRepository::class,
            MessagingIterationResponsesRepository::class => \EmployeesSmartEvaluering\Repositories\MessagingIterationResponsesRepository::class,
        ])->each(function ($implementation, $interface) {
            $this->app->bind($interface, $implementation);
        });
    }

    public function register()
    {
        app('router')->aliasMiddleware('employees-se-auth-data-scopes', AuthDataScopes::class);
    }

    private function bindFilesPaths() {
        $this->publishes([realpath(__DIR__.'/../resources/views') => resource_path('views/vendor/employees-se')], 'employees-views');
        $this->publishes([realpath(__DIR__.'/../resources/assets/js') => resource_path('assets/js/employees-se')], 'employees-js');
        $this->publishes([realpath(__DIR__.'/../resources/assets/sass') => resource_path('assets/sass/employees-se')], 'employees-sass');
        $this->publishes([
            realpath(__DIR__.'/../resources/views')       => resource_path('views/vendor/employees-se'),
            realpath(__DIR__.'/../resources/assets/js')   => resource_path('assets/js/employees-se'),
            realpath(__DIR__.'/../resources/assets/sass') => resource_path('assets/less/employees-se'),
        ], 'employees-full');
    }

    private function bindCommands() {
        $this->commands([
            CreateMockEmployees::class,
        ]);
    }

    private function bindMessagesSender(Request $request) {
        $this->app->bind(SmartEvalueringSenderContract::class, function(Application $app) use ($request) {
            $user = !$app->runningInConsole() ? $request->user() : Role::bySlug(Role::SUPER_ADMIN)->users()->orderBy('id')->first();

            return new SenderAuthAdapter($user->id);
        });
    }
}