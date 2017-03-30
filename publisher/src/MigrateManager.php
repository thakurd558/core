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
 * @author     Original Orchestral https://github.com/orchestral
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares Project
 * @link       http://antaresproject.io
 */


namespace Antares\Publisher;

use Antares\Extension\Manager;
use Illuminate\Database\Seeder as IlluminateSeeder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Migrations\Migrator;
use Antares\Contracts\Publisher\Publisher;
use Closure;
use Illuminate\Support\Facades\File;
use stdClass;

class MigrateManager implements Publisher
{

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * seeder instance.
     *
     * @var IlluminateSeeder
     */
    protected $seeder;

    /**
     * Extensions manager instance.
     *
     * @var Manager
     */
    protected $manager;

    /**
     * Construct a new instance.
     * 
     * @param Container $app
     * @param Migrator $migrator
     * @param IlluminateSeeder $seeder
     */
    public function __construct(Container $app, Migrator $migrator, IlluminateSeeder $seeder)
    {
        $this->app      = $app;
        $this->migrator = $migrator;
        $this->seeder   = $seeder;
        $this->manager = app()->make('antares.extension');
    }

    /**
     * Create migration repository if it's not available.
     *
     * @return void
     */
    protected function createMigrationRepository()
    {
        $repository = $this->migrator->getRepository();

        if (!$repository->repositoryExists()) {
            $repository->createRepository();
        }
    }

    /**
     * Run migration for an extension or application.
     *
     * @param  string  $path
     *
     * @return void
     */
    public function run($path)
    {
        $this->createMigrationRepository();
        $this->migrator->run($path);
    }

    /**
     * Migrate package.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function package($name)
    {
        if (starts_with($name, 'src')) {
            $name = str_replace('src/', '', $name);
        }
        $basePath   = rtrim($this->app->make('path.base'), '/');
        $vendorPath = "{$basePath}/src";
        $paths      = [
            "{$vendorPath}/{$name}/resources/database/migrations/",
            "{$vendorPath}/{$name}/database/migrations/",
            "{$vendorPath}/{$name}/migrations/",
        ];
        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                $this->run($path);
            }
        }
        $seeds = [
            "{$vendorPath}/{$name}/resources/database/seeds/",
            "{$vendorPath}/{$name}/database/seeds/",
            "{$vendorPath}/{$name}/seeds/",
        ];
        $this->seed($name, $seeds);
    }

    /**
     * resolve paths of migrations & seeds
     * 
     * @param String $name
     * @param String $directory
     * @return array
     */
    protected function getPaths($name, $directory = 'migrations')
    {
        $package = $this->manager->getAvailableExtensions()->findByName($name);

        if($package === null) {
            return [];
        }

        $basePath = $package->getPath();

        $paths = [
            "{$basePath}/resources/database/{$directory}/",
            "{$basePath}/resources/{$directory}/",
            "{$basePath}/src/{$directory}/",
        ];

        return $paths;
    }

    /**
     * Migrate extension.
     * 
     * @param  string  $name
     * @return void
     */
    public function extension($name)
    {
        $paths = $this->getPaths($name);

        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                $this->run($path);
            }
        }

        $this->seed($name);
    }

    /**
     * run seeds from all files in seeds directory
     * 
     * @param string $name
     * @param string|array $paths
     */
    public function seed($name, $paths = null)
    {
        $directories = count($paths) ? $paths : $this->getPaths($name, 'seeds');

        $this->eachFileInPaths($directories, function($file) {
            $class = $this->prepareSeedClass($file);

            if ($class !== null) {
                $this->seeder->call($class);
            }
        });
    }

    /**
     * get uninstall pathes
     * 
     * @param String $name
     * @param String $directory
     * @return array
     */
    protected function uninstallPaths($name, $directory = 'migrations')
    {
        $package = $this->manager->getAvailableExtensions()->findByName($name);

        if($package === null) {
            return [];
        }

        $basePath = $package->getPath();

        return [
            "{$basePath}/resources/database/{$directory}/",
            "{$basePath}/resources/{$directory}/",
            "{$basePath}/src/{$directory}/",
        ];
    }

    /**
     * prepare seed classname
     * 
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return String
     */
    protected function prepareSeedClass($file)
    {
        $extension = $file->getExtension();
        if ($extension !== 'php') {
            return null;
        }
        return '\\' . str_replace('.' . $extension, '', $file->getFilename());
    }

    /**
     * 
     * @param string $name
     */
    public function unSeed($name)
    {
        $paths = $this->uninstallPaths($name, 'seeds');

        $this->eachFileInPaths($paths, function($file) {
            $className = $this->prepareSeedClass($file);

            if ($className !== null) {
                $seedInstance = new $className;

                if( method_exists($seedInstance, 'down') ) {
                    $seedInstance->down();
                }
            }
        });
    }

    /**
     * uninstalling component
     * @param string $name
     */
    public function uninstall($name)
    {
        $this->unSeed($name);
        $paths = $this->uninstallPaths($name);

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $files = $this->migrator->getMigrationFiles($path);
            $this->migrator->requireFiles($path, $files);

            foreach ($files as $file) {
                $migration           = $this->migrator->resolve($file);
                $migrator            = new stdClass();
                $migrator->migration = $file;

                $this->migrator->getRepository()->delete($migrator);

                if (method_exists($migration, 'down')) {
                    $migration->down();
                }
            }
        }
    }

    /**
     * Migrate Antares.
     *
     * @return void
     */
    public function foundation()
    {
        $this->package('core/memory');
        $this->package('core/auth');
        $this->package('core/form');
    }

    /**
     * Calls callbacks for each file in the given paths.
     *
     * @param array $paths
     * @param Closure $callback
     */
    private function eachFileInPaths(array $paths, Closure $callback)
    {
        foreach($paths as $path)
        {
            if (! File::isDirectory($path)) {
                continue;
            }

            $files = File::allFiles($path);

            foreach($files as $file) {
                $callback($file);
            }
        }
    }

}
