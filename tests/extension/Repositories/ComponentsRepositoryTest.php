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

namespace Antares\Extension\Repositories\TestCase;

use Antares\Extension\Repositories\ComponentsRepository;

class ComponentsRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Components with branches.
     *
     * @var array
     */
    protected $branches = [
        'aaa' => '1.0',
        'bbb' => '1.1',
        'ccc' => '2.0',
    ];

    /**
     * Test fetching components with branches.
     */
    public function testGetBranches() {
        $repository = new ComponentsRepository($this->branches, [], []);

        $this->assertEquals($this->branches, $repository->getBranches());
    }

    /**
     * Test fetching required components with branches.
     */
    public function testGetRequired() {
        $required = [
            'aaa',
            'ccc',
        ];

        $expected = [
            'aaa' => '1.0',
            'ccc' => '2.0',
        ];

        $repository = new ComponentsRepository($this->branches, $required, []);

        $this->assertEquals($expected, $repository->getRequired());
    }

    /**
     * Test fetching required components without specified branches.
     */
    public function testGetRequiredWithNoBranch() {
        $required = [
            'aaa',
            'ddd',
        ];

        $expected = [
            'aaa' => '1.0',
            'ddd' => 'dev-master',
        ];

        $repository = new ComponentsRepository($this->branches, $required, []);

        $this->assertEquals($expected, $repository->getRequired());
    }

    /**
     * Test fetching optional components with branches.
     */
    public function testGetOptional() {
        $optional = [
            'aaa',
            'ccc',
        ];

        $expected = [
            'aaa' => '1.0',
            'ccc' => '2.0',
        ];

        $repository = new ComponentsRepository($this->branches, [], $optional);

        $this->assertEquals($expected, $repository->getOptional());
    }

    /**
     * Test fetching optional components without specified branches.
     */
    public function testGetOptionalWithNoBranch() {
        $optional = [
            'aaa',
            'ddd',
        ];

        $expected = [
            'aaa' => '1.0',
            'ddd' => 'dev-master',
        ];

        $repository = new ComponentsRepository($this->branches, [], $optional);

        $this->assertEquals($expected, $repository->getOptional());
    }

    /**
     * Test fetching target branch by given component.
     */
    public function testGetTargetBranch() {
        $repository = new ComponentsRepository($this->branches, [], []);

        $this->assertEquals('1.1', $repository->getTargetBranch('bbb'));
        $this->assertEquals('dev-master', $repository->getTargetBranch('eee'));
    }

    /**
     * Test fetching component with branches by specifies only components.
     */
    public function testGetWithBranches() {
        $repository = new ComponentsRepository($this->branches, [], []);

        $expected = [
            'aaa' => '1.0',
            'eee' => 'dev-master',
        ];

        $this->assertEquals($expected, $repository->getWithBranches(array_keys($expected)));
    }

}