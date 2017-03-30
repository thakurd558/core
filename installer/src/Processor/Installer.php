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

namespace Antares\Installation\Processor;

use Antares\Extension\Contracts\ExtensionContract;
use Antares\Extension\Jobs\BulkExtensionsBackgroundJob;
use Antares\Extension\Manager;
use Antares\Installation\Http\Controllers\InstallerController;
use Antares\Installation\Http\Form\License as LicenseForm;
use Antares\Contracts\Installation\Requirement;
use Antares\Installation\Installation;
use Antares\Installation\Progress;
use Antares\Installation\Repository\Components;
use Antares\Installation\Repository\License;
use Antares\Support\Facades\Config;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Antares\Support\Facades\Form;
use Illuminate\Cache\FileStore;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ReflectionException;
use Antares\Model\User;
use Exception;
use Illuminate\Support\Facades\File;

class Installer
{

    /**
     * Installer instance.
     *
     * @var Installation
     */
    protected $installer;

    /**
     * Requirement instance.
     *
     * @var Requirement
     */
    protected $requirement;

    /**
     * components repository instance
     *
     * @var Components
     */
    protected $components;

    /**
     * license repository instance
     *
     * @var License
     */
    protected $license;

    /**
     * Create a new processor instance.
     *
     * @param Installation $installer
     * @param Requirement $requirement
     * @param Components $components
     * @param License $license
     */
    public function __construct(Installation $installer, Requirement $requirement, Components $components, License $license)
    {
        $this->installer   = $installer;
        $this->requirement = $requirement;
        $this->installer->bootInstallerFiles();
        $this->components  = $components;
        $this->license     = $license;
    }

    /**
     * Start an installation and check for requirement.
     *
     * @param  object  $listener
     *
     * @return mixed
     */
    public function index($listener)
    {
        $requirement = $this->requirement;
        $installable = $requirement->check();
        list($database, $auth, $authentication) = $this->getRunningConfiguration();

        (true === $authentication) || $installable = false;

        $data = [
            'database'       => $database,
            'auth'           => $auth,
            'authentication' => $authentication,
            'installable'    => $installable,
            'checklist'      => $requirement->getChecklist(),
        ];
        $this->clearStorage();
        $this->clearCache();
        return $listener->indexSucceed($data);
    }

    /**
     * Clearing storage files before installation
     */
    protected function clearStorage()
    {
        $finder     = new Finder();
        $paths      = (array) config('antares/installer::storage_path', []);
        $finder     = $finder->files()->ignoreVCS(true);

        foreach ($paths as $path) {
            $current = storage_path($path);

            if (!is_dir($current)) {
                continue;
            }
            $finder = $finder->in($current);
        }

        $finder->exclude('.gitignore');

        foreach ($finder as $element) {
            File::delete($element);
        }
        try {
            $directories = $finder->directories();
            foreach ($directories as $dir) {
                $files = File::allFiles($dir->getPath(), true);
                if (empty($files)) {
                    File::deleteDirectory($dir->getPath());
                }
            }
        } catch (Exception $e) {

        }
        return;
    }

    /**
     * clear global cache
     *
     * @return boolean
     */
    protected function clearCache()
    {
        try {
            $cache = app('cache');
            $store = $cache->store()->getStore();
            if ($store instanceof FileStore) {
                $directory = $store->getDirectory();
                $store->getFilesystem()->cleanDirectory($directory);
                return true;
            }
        } catch (Exception $e) {
            Log::emergency($e);
            return false;
        }
    }

    /**
     * Run migration and prepare the database.
     *
     * @param  object  $listener
     *
     * @return mixed
     */
    public function prepare($listener)
    {
        $this->clearCache();
        $this->installer->migrate();
        return $listener->prepareSucceed();
    }

    /**
     * processing store license details
     *
     * @param InstallerController $listener
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function license($listener, Request $request)
    {
        $form = LicenseForm::getInstance();
        if (!$request->isMethod('post')) {
            return $listener->showLicenseForm($form);
        }
        $enabled = config('license.enabled');
        if ($enabled) {
            $uploaded = $this->license->uploadLicense($request);

            if (!$uploaded) {
                return $listener->licenseFailedStore();
            }
            if (!$form->isValid()) {
                return $listener->licenseFailedValidation($form->getMessageBag());
            }
        }
        return $listener->licenseSuccessStore();
    }

    /**
     * Display initial user and site configuration page.
     *
     * @param  object  $listener
     *
     * @return mixed
     */
    public function create($listener)
    {
        return $listener->createSucceed(['siteName' => 'Antares',]);
    }

    /**
     * Store/save administator information and site configuration.
     *
     * @param  object  $listener
     * @param  array   $input
     *
     * @return mixed
     */
    public function store($listener, array $input)
    {
        if (!$this->installer->createAdmin($input)) {
            return $listener->storeFailed();
        }
        return $listener->storeSucceed();
    }

    /**
     * launch components/modules installation.
     *
     * @param  object  $listener
     * @param  array   $selected
     *
     * @return mixed
     */
    public function storeComponents($listener, array $selected)
    {
        try {
            $required   = (array) config('installer.required', []);
            $extensions = array_merge($required, $selected);

            $memory     = app()->make('antares.memory')->make('primary');
            $memory->put('app.installation.components', $extensions);

            /* @var $progress Progress */
            $progress   = app()->make(Progress::class);
            $progress->start();
            $progress->save();

            $memory->finish();

            $job = new BulkExtensionsBackgroundJob($extensions, \Antares\Extension\Processors\Installer::class, $progress->getFilePath());
            $job->onQueue('installation');

            dispatch($job);
        }
        catch (Exception $e) {
            Log::emergency($e);
            return $listener->doneFailed();
        }

        return $listener->showInstallProgress();
    }

    /**
     * shows components form
     *
     * @param object $listener
     * @return mixed
     */
    public function components($listener)
    {
        $form = Form::of('components', function ($form) {
            $attributes = [
                'url'    => handles("antares::install/components/store"),
                'method' => 'POST'
            ];

            $form->attributes($attributes);
            $form->name('Components list');
            $list = $this->getComponentsList();

            $form->fieldset(function ($fieldset) use($list) {
                $fieldset->legend('Required components');
                $required = (array) array_get($list, 'required', []);

                /* @var $extension ExtensionContract */
                foreach ($required as $extension) {
                    $name = $extension->getPackage()->getName();

                    $data = [
                        'description'   => $extension->getPackage()->getDescription(),
                        'version'       => $extension->getPackage()->getPrettyVersion(),
                    ];

                    $fieldset->control('input:checkbox', 'required[]')
                        ->label($name)
                        ->value($name)
                        ->help(implode(', ', $data))
                        ->checked()
                        ->attributes(['disabled' => 'disabled', 'readonly' => 'readonly']);
                }
            });

            $form->fieldset(function ($fieldset) use($list) {
                $fieldset->legend('Available optional components');
                $optional = (array) array_get($list, 'optional', []);

                /* @var $extension ExtensionContract */
                foreach ($optional as $extension) {
                    $name = $extension->getPackage()->getName();

                    $data = [
                        'description'   => $extension->getPackage()->getDescription(),
                        'version'       => $extension->getPackage()->getPrettyVersion(),
                    ];

                    $fieldset->control('input:checkbox', 'extension[]')
                        ->label($name)
                        ->value($name)
                        ->help(implode(', ', $data));
                }

                $fieldset->control('button', 'cancel')
                    ->field(function() {
                        return app('html')->link(handles("antares::install/create"), trans('antares/foundation::label.cancel'), ['class' => 'btn btn--md btn--default mdl-button mdl-js-button']);
                    });

                $fieldset->control('button', 'button')
                    ->attributes(['type' => 'submit', 'class' => 'btn btn--md btn--primary mdl-button mdl-js-button'])
                    ->value(trans('antares/foundation::label.next'));
            });
        });

        return $listener->componentsSucceed(['form' => $form]);
    }

    /**
     * Gets list of available components
     *
     * @return array
     */
    protected function getComponentsList()
    {
        /* @var $manager Manager */
        $manager    = app()->make(Manager::class);
        $extensions = $manager->getAvailableExtensions();

        $requiredConfig = (array) config('installer.required', []);

        $required = [];
        $optional = [];

        foreach($extensions as $extension) {
            $name = $extension->getPackage()->getName();

            if( in_array($name, $requiredConfig, true) ) {
                $required[] = $extension;
            }
            else {
                $optional[] = $extension;
            }
        }

        return compact('required', 'optional');
    }

    /**
     * Complete the installation.
     *
     * @param  object  $listener
     *
     * @return mixed
     */
    public function done($listener)
    {
        return $listener->doneSucceed();
    }

    /**
     * Get running configuration.
     *
     * @return array
     */
    protected function getRunningConfiguration()
    {
        $driver   = Config::get('database.default', 'mysql');
        $database = Config::get("database.connections.{$driver}", []);
        $auth     = Config::get('auth');

        if (isset($database['password']) && ($password = strlen($database['password']))) {
            $database['password'] = str_repeat('*', $password);
        }

        $authentication = $this->isAuthenticationInstallable($auth);

        return [$database, $auth, $authentication];
    }

    /**
     * Is authentication installable.
     *
     * @param  array    $auth
     *
     * @return bool
     */
    protected function isAuthenticationInstallable($auth)
    {
        try {
            $eloquent = App::make($auth['providers']['users']['model']);
            return ($auth['providers']['users']['driver'] === 'eloquent' && $eloquent instanceof User);
        } catch (ReflectionException $e) {
            Log::emergency($e);
            return false;
        }
    }

}
