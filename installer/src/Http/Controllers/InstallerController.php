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

namespace Antares\Installation\Http\Controllers;

use Antares\Installation\Processor\Installer as InstallerProcessor;
use Antares\Foundation\Http\Controllers\BaseController;
use Antares\Installation\Processor\Installer;
use Antares\Installation\Progress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallerController extends BaseController
{

    /**
     * The layout that should be used for responses.
     */
    protected $layout = 'antares/foundation::layouts.installer.main';

    /**
     * Construct Installer controller.
     *
     * @param  Installer  $processor
     */
    public function __construct(InstallerProcessor $processor)
    {
        $this->processor = $processor;
        set_meta('navigation::usernav', false);
        set_meta('title', 'Installer');

        parent::__construct();
    }

    /**
     * Setup controller filters.
     *
     * @return void
     */
    protected function setupMiddleware()
    {
        $this->middleware('antares.installed', ['only' => ['index', 'create', 'store'],]);
    }

    /**
     * Check installation requirement page.
     *
     * GET (:antares)/install
     *
     * @return mixed
     */
    public function index()
    {
        return $this->processor->index($this);
    }

    /**
     * Migrate database schema for Antares.
     *
     * GET (:antares)/install/prepare
     *
     * @return mixed
     */
    public function prepare()
    {
        return $this->processor->prepare($this);
    }

    /**
     * Show create adminstrator page.
     *
     * GET (:antares)/install/create
     *
     * @return mixed
     */
    public function create()
    {
        return $this->processor->create($this);
    }

    /**
     * Create an adminstrator.
     *
     * POST (:antares)/install/create
     *
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        return $this->processor->store($this, $request->all());
    }

    /**
     * End of installation.
     *
     * GET (:antares)/install/done
     *
     * @return mixed
     */
    public function done()
    {
        return $this->processor->done($this);
    }

    /**
     * Response for installation welcome page.
     *
     * @param  array   $data
     *
     * @return mixed
     */
    public function indexSucceed(array $data)
    {
        return view('antares/installer::index', $data);
    }

    /**
     * Response when installation is prepared.
     *
     * @return mixed
     */
    public function prepareSucceed()
    {
        /* @var $progress Progress */
        $progress = app()->make(Progress::class);

        if($progress->isFailed()) {
            $progress->reset();
        }

        return $this->redirect(handles('antares::install/create'));
    }

    /**
     * Response view to input user information for installation.
     *
     * @param  array   $data
     *
     * @return mixed
     */
    public function createSucceed(array $data)
    {
        return view('antares/installer::create', $data);
    }

    /**
     * Response when store installation config is failed.
     *
     * @return mixed
     */
    public function storeFailed()
    {
        return $this->redirect(handles('antares::install/create'));
    }

    /**
     * Response when store installation config is succeed.
     *
     * @return mixed
     */
    public function storeSucceed()
    {
        return redirect()->to(handles('antares::install/progress'));
    }

    /**
     * Response when installation is done.
     *
     * @return mixed
     */
    public function doneSucceed()
    {
        /* @var $progress Progress */
        $progress = app()->make(Progress::class);
        $progress->reset();

        app('antares.messages')->add('success', trans('Installation is completed. Now you can login to administration area.'));
        return $this->redirect(handles('antares::install/completed'));
    }

    /**
     * Response when installation throws exception and is not done.
     *
     * @return mixed
     */
    public function doneFailed()
    {
        /* @var $progress Progress */
        $progress = app()->make(Progress::class);
        $progress->reset();

        app('antares.messages')->add('error', trans('Installation failed. Please try again or contact with software provider.'));
        return $this->redirect(handles('antares::install/failed'));
    }

    /**
     * when installation is completed
     * 
     * @return View
     */
    public function completed()
    {
        /* @var $progress Progress */
        $progress = app()->make(Progress::class);
        $progress->reset();

        return view('antares/installer::installation.completed');
    }

    /**
     * When installation is failed.
     *
     * @param Progress $progress
     * @return \Illuminate\Contracts\View\Factory|View
     */
    public function failed(Progress $progress)
    {
        $additionalMessage = $progress->getFailedMessage();

        return view('antares/installer::installation.failed', compact('additionalMessage'));
    }

}
