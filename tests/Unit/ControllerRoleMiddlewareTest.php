<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SSOController;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Traits\HasRoles;
use Tests\TestCase;

class ControllerRoleMiddlewareTest extends TestCase
{
    public function test_user_model_uses_spatie_has_roles_trait_and_role_middleware_alias_is_registered(): void
    {
        $traits = class_uses_recursive(User::class);

        $this->assertArrayHasKey(HasRoles::class, $traits);
        $this->assertTrue(method_exists(User::class, 'hasAnyRole'));

        $kernel = app(Kernel::class);
        $middlewareAliases = $kernel->getMiddlewareAliases();
        $this->assertSame(RoleMiddleware::class, $middlewareAliases['role'] ?? null);
        $this->assertSame(RoleMiddleware::class, app('router')->getMiddleware()['role'] ?? null);
    }

    public function test_all_admin_controllers_and_home_controller_require_super_admin_role(): void
    {
        $adminDir = app_path('Http/Controllers/Admin');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminDir));
        $controllerClasses = [HomeController::class];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace(
                [app_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'],
                ['', '\\', ''],
                $file->getPathname()
            );

            $controllerClasses[] = 'App\\'.$relative;
        }

        sort($controllerClasses);
        $this->assertCount(26, $controllerClasses);

        foreach ($controllerClasses as $controllerClass) {
            $controller = app()->make($controllerClass);
            $middlewares = collect($controller->getMiddleware())->pluck('middleware')->all();

            $this->assertContains(
                'role:super admin',
                $middlewares,
                "Controller {$controllerClass} wajib mendaftarkan middleware role:super admin di __construct()."
            );
        }

        $homeMiddlewares = collect(app()->make(HomeController::class)->getMiddleware())->pluck('middleware')->all();
        $this->assertContains('auth', $homeMiddlewares);
    }

    public function test_auth_and_sso_controllers_remain_accessible_without_super_admin_role(): void
    {
        $publicAuthControllers = [
            ConfirmPasswordController::class,
            ForgotPasswordController::class,
            LoginController::class,
            RegisterController::class,
            ResetPasswordController::class,
            VerificationController::class,
            SSOController::class,
        ];

        foreach ($publicAuthControllers as $controllerClass) {
            $controller = app()->make($controllerClass);
            $middlewares = collect($controller->getMiddleware())->pluck('middleware')->all();

            $this->assertNotContains(
                'role:super admin',
                $middlewares,
                "Controller {$controllerClass} tidak boleh dibatasi role:super admin."
            );
        }
    }
}
