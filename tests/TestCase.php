<?php

namespace Webard\Biloquent\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;

use function Orchestra\Testbench\artisan;
use function Orchestra\Testbench\workbench_path;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;
    //use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {

        $this->loadMigrationsFrom(workbench_path('database/migrations'));
        artisan($this, 'migrate');

        $this->beforeApplicationDestroyed(
            fn () => artisan($this, 'migrate:rollback')
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Staudenmeir\LaravelCte\DatabaseServiceProvider::class,
        ];
    }
}
