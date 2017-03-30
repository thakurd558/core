<?php

/**
 * Part of the Antares Project package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Antares Core
 * @version    0.9.0
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares Project
 * @link       http://antaresproject.io
 */

namespace Antares\Publisher\TestCase;

use Antares\Extension\Collections\Extensions;
use Antares\Extension\Contracts\ExtensionContract;
use Antares\Testbench\ApplicationTestCase;
use Antares\Publisher\MigrateManager;
use Mockery as m;

class MigrateManagerTest extends ApplicationTestCase
{

    /**
     * Test Antares\Publisher\MigrateManager::run() method.
     *
     * @test
     */
    public function testRunMethod()
    {
        $migrator   = m::mock('\Illuminate\Database\Migrations\Migrator');
        $repository = m::mock('\Illuminate\Database\Migrations\DatabaseMigrationRepository');
        $seeder     = m::mock('\Illuminate\Database\Seeder');

        $this->app['antares.extension'] = m::mock('\Antares\Extension\Manager');


        $migrator->shouldReceive('getRepository')->once()->andReturn($repository)
                ->shouldReceive('run')->once()->with('/foo/path/migrations')->andReturn(null);
        $repository->shouldReceive('repositoryExists')->once()->andReturn(false)
                ->shouldReceive('createRepository')->once()->andReturn(null);

        $stub = new MigrateManager($this->app, $migrator, $seeder);
        $stub->run('/foo/path/migrations');
    }

    /**
     * Test Antares\Publisher\MigrateManager::extension() method.
     *
     * @test
     */
    public function testExtensionMethod()
    {
        $app = $this->app;

        $app['migrator']                 = $migrator                        = m::mock('\Illuminate\Database\Migrations\Migrator');
        $app['files']                    = $files                           = m::mock('\Illuminate\Filesystem\Filesystem');
        $app['antares.extension'] = $extension                          = m::mock('\Antares\Extension\Manager');

        $repository = m::mock('\Illuminate\Database\Migrations\DatabaseMigrationRepository');

        $packages = m::mock(ExtensionContract::class)
            ->shouldReceive('getPath')->twice()->andReturn('/foo/path/foo/bar')->getMock()
            ->shouldReceive('getPath')->twice()->andReturn('/foo/app/foo/bar')->getMock()
            ->shouldReceive('getPath')->times(4)->andReturn('/foo/path/laravel/framework')->getMock();

        $extensionsCollection = m::mock(Extensions::class)
            ->shouldReceive('findByName')->twice()->with('foo/bar')->andReturn($packages)->getMock()
            ->shouldReceive('findByName')->twice()->with('foo/bar')->andReturn($packages)->getMock()
            ->shouldReceive('findByName')->times(4)->with('laravel/framework')->andReturn($packages)->getMock();

        $extension->shouldReceive('getAvailableExtensions-')->twice()->andReturn($extensionsCollection)->getMock()
                ->shouldReceive('getAvailableExtensions')->twice()->andReturn($extensionsCollection)->getMock()
                ->shouldReceive('getAvailableExtensions')->times(4)->andReturn($extensionsCollection)->getMock();



        $files->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/resources/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/resources/database/seeds/')->andReturn(true)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/resources/seeds/')->andReturn(true)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/src/seeds/')->andReturn(true)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/resources/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/src/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/resources/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/resources/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/src/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/resources/database/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/resources/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/src/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/resources/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/resources/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/src/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/resources/database/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/resources/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/src/seeds/')->andReturn(false)
                ->shouldReceive('allFiles')->times(3)->andReturn([]);


        $repository->shouldReceive('createRepository')->never()->andReturn(null);

        $seeder = m::mock('\Illuminate\Database\Seeder');

        $stub = new MigrateManager($app, $migrator, $seeder);
        $stub->extension('foo/bar');
        $stub->extension('laravel/framework');
    }

    /**
     * Test Antares\Publisher\MigrateManager::foundation() method.
     *
     * @test
     */
    public function testFoundationMethod()
    {
        $app = $this->app;

        $app['files']               = $files            = m::mock('\Illuminate\Filesystem\Filesystem');
        $app['migrator']            = $migrator         = m::mock('\Illuminate\Database\Migrations\Migrator');
        $app['antares.extension']   = $extension        = m::mock('\Antares\Extension\Manager');
        $app['path.base'] = '/foo/path/';

        $repository = m::mock('\Illuminate\Database\Migrations\DatabaseMigrationRepository');

        $files->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/memory/resources/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/memory/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/memory/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/memory/resources/database/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/memory/database/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/memory/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/auth/resources/database/migrations/')->andReturn(true)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/auth/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/auth/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/auth/resources/database/seeds/')->andReturn(true)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/auth/database/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/auth/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/form/resources/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/form/database/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/form/migrations/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/form/resources/database/seeds/')->andReturn(true)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/form/database/seeds/')->andReturn(false)
                ->shouldReceive('isDirectory')->once()->with('/foo/path/src/core/form/seeds/')->andReturn(false)
                ->shouldReceive('allFiles')->times(2)->andReturn([]);

        $migrator->shouldReceive('getRepository')->once()->andReturn($repository)
                ->shouldReceive('run')->once()->with('/foo/path/src/core/auth/resources/database/migrations/')->andReturn(null);
        $repository->shouldReceive('repositoryExists')->once()->andReturn(true)
                ->shouldReceive('createRepository')->never()->andReturn(null);

        $seeder = m::mock('\Illuminate\Database\Seeder');

        $stub = new MigrateManager($app, $migrator, $seeder);
        $stub->foundation();
    }

}
