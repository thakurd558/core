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


namespace Antares\Brands\Traits;

use Antares\Brands\Scope\ActiveBrandScope;

trait ActiveBrandTrait
{

    /**
     * Boot the Active Events trait for a model.
     *
     * @return void
     */
    public static function bootActiveBrandTrait()
    {
        static::addGlobalScope(new ActiveBrandScope());
    }

}
