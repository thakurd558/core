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

namespace Antares\Extension\Factories\TestCase;

use Antares\Extension\Factories\SettingsFactory;

use Illuminate\Filesystem\Filesystem;
use Mockery as m;

class SettingsFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Filesystem mockery.
     *
     * @var \Mockery\MockInterface
     */
    protected $filesystem;

    /**
     * {@inheritDoc}
     */
    public function setUp() {
        parent::setUp();

        $this->filesystem    = m::mock(Filesystem::class);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown() {
        parent::tearDown();
        m::close();
    }

    /**
     * Returns settings factory instance.
     *
     * @return SettingsFactory
     */
    protected function getFactory() {
        return new SettingsFactory($this->filesystem);
    }

    /**
     * Test is all data are empty.
     */
    public function testCreateFromDataMethodAsEmpty() {
        $configData = [];

        $settings = $this->getFactory()->createFromData($configData);

        $this->assertEquals([], $settings->getData());
        $this->assertEquals([], $settings->getValidationRules());
        $this->assertEquals([], $settings->getValidationPhrases());
    }

    /**
     * Test if data are correctly returned.
     */
    public function testCreateFromDataMethodWithConfig() {
        $data = [
            'a' => 'foo',
            'b' => 'bar',
        ];

        $rules = [
            'a' => 'foo',
            'b' => 'bar',
        ];

        $phrases = [
            'a' => 'foo',
            'b' => 'bar',
        ];

        $configData = compact('data', 'rules', 'phrases');

        $settings = $this->getFactory()->createFromData($configData);

        $this->assertEquals($data, $settings->getData());
        $this->assertEquals($rules, $settings->getValidationRules());
        $this->assertEquals($phrases, $settings->getValidationPhrases());
    }

    /**
     * Test creating from config file.
     */
    public function testCreateFromConfig() {
        $data = [
            'a' => 'foo',
            'b' => 'bar',
        ];

        $rules = [
            'a' => 'foo',
            'b' => 'bar',
        ];

        $phrases = [
            'a' => 'foo',
            'b' => 'bar',
        ];

        $dumpConfigPath = 'foo/bar';
        $configData     = compact('data', 'rules', 'phrases');

        $this->filesystem->shouldReceive('getRequire')
            ->once()
            ->with($dumpConfigPath)
            ->andReturn($configData)
            ->getMock();

        $settings = $this->getFactory()->createFromConfig($dumpConfigPath);

        $this->assertEquals($data, $settings->getData());
        $this->assertEquals($rules, $settings->getValidationRules());
        $this->assertEquals($phrases, $settings->getValidationPhrases());
    }

}