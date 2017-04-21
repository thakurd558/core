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

namespace Antares\Extension\TestCase;

use Antares\Extension\Contracts\ExtensionContract;
use Antares\Extension\Processors\Acl;
use Antares\Extension\Processors\Activator;
use Antares\Extension\Repositories\ComponentsRepository;
use Antares\Extension\Repositories\ExtensionsRepository;
use Mockery as m;

class ActivatorTest extends OperationSetupTestCase
{

    /**
     * Extension repository mockery.
     *
     * @var \Mockery\MockInterface
     */
    protected $extensionsRepository;

    /**
     * ACL manager mockery.
     *
     * @var \Mockery\MockInterface
     */
    protected $aclMigration;

    /**
     * Components repository mockery.
     *
     * @var \Mockery\MockInterface
     */
    protected $componentRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();

        $this->extensionsRepository = m::mock(ExtensionsRepository::class);
        $this->aclMigration         = m::mock(Acl::class);
        $this->componentRepository  = m::mock(ComponentsRepository::class);
    }

    /**
     * Returns processor instance.
     *
     * @return Activator
     */
    public function getOperationProcessor() {
        return new Activator($this->container, $this->dispatcher, $this->kernel, $this->extensionsRepository, $this->aclMigration, $this->componentRepository);
    }

    /**
     * Test if activation finished successfully.
     */
    public function testAsSuccess() {
        $processor = $this->getOperationProcessor();

        $handler = $this->buildOperationHandlerMock()
            ->shouldReceive('operationInfo')
            ->andReturnNull()
            ->getMock()
            ->shouldReceive('operationSuccess')
            ->once()
            ->andReturnNull()
            ->getMock();

        $name = 'foo/bar';
        $extension = $this->buildExtensionMock($name)
            ->shouldReceive('getPath')
            ->andReturn('/src/component/foo/bar')
            ->getMock();

        $this->dispatcher->shouldReceive('fire')->twice()->andReturnNull()->getMock();
        $this->aclMigration->shouldReceive('import')->once()->with($handler, $extension)->andReturnNull()->getMock();

        $this->extensionsRepository->shouldReceive('save')->once()->with($extension, [
            'status' => ExtensionContract::STATUS_ACTIVATED,
        ])->andReturnNull()->getMock();

        $processor->run($handler, $extension);
    }

    /**
     * Test if activation finished with exception.
     */
    public function testWithException() {
        $processor = $this->getOperationProcessor();

        $handler = $this->buildOperationHandlerMock()
            ->shouldReceive('operationInfo')
            ->andReturnNull()
            ->getMock()
            ->shouldReceive('operationFailed')
            ->once()
            ->andReturnNull()
            ->getMock();

        $name = 'foo/bar';
        $extension = $this->buildExtensionMock($name)
            ->shouldReceive('getPath')
            ->andReturn('/src/component/foo/bar')
            ->getMock();

        $this->dispatcher->shouldReceive('fire')->twice()->andReturnNull()->getMock();
        $this->aclMigration->shouldReceive('import')->once()->with($handler, $extension)->andThrow(\Exception::class)->getMock();

        $processor->run($handler, $extension);
    }

}
