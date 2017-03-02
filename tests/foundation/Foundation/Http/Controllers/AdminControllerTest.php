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

namespace Antares\Foundation\Http\Controllers\TestCase;

use Antares\Foundation\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route as FacadeRoute;
use Antares\Testing\ApplicationTestCase;
use Illuminate\Routing\Route;
use Mockery as m;

class AdminControllerTest extends ApplicationTestCase
{

    /**
     * Test Antares\Foundation\Http\Controllers\AdminController filters.
     *
     * @test
     */
    public function testMiddleware()
    {
        $stub  = new StubAdminController();
        $route = m::mock(Route::class);
        $route->shouldReceive('getAction')->andReturn(['controller' => 'IndexController@index']);

        FacadeRoute::shouldReceive('getCurrentRoute')->andReturn($route);
        $this->assertNull($stub->middleware('antares.can:foo', ['only' => ['index', 'add']]));
    }

}

class StubAdminController extends AdminController
{

    protected function setupFilters()
    {
        
    }

    public function setupMiddleware()
    {
        ;
    }

}
