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

namespace Antares\Memory\Model;

use Antares\Memory\Exception\PermissionNotSavedException;
use Antares\Model\Permission as PermissionModel;
use Antares\Brands\Model\Brands;
use Antares\Model\Component;
use Antares\Model\Eloquent;
use Antares\Model\Action;
use Antares\Model\Role;
use Exception;
use Cache;
use Illuminate\Database\Eloquent\Collection;
use Log;
use DB;

/**
 * Class Permission
 * @package Antares\Memory\Model
 * @property int $id
 * @property string $name
 * @property string $vendor
 * @property int $status
 * @property bool $required
 * @property array $options
 * @property string $actions
 * @property string $permissions
 * @property Action[]|Collection $attachedActions
 */
class Permission extends Eloquent
{

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'permissions';

    /**
     * The class name to be used in polymorphic relations.
     * @var string
     */
    protected $morphClass = 'Permission';

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'id'        => 'integer',
        'brand_id'  => 'integer',
        'status'    => 'integer',
        'required' 	=> 'boolean',
        'options'   => 'array',
    ];

    /**
     * Fetching all permissions.
     *
     * @param int|null $brandId
     * @return Permission[]|\Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function fetchAll($brandId = null)
    {
        $query = Permission::query()->with('attachedActions');

        if($brandId !== null) {
            $query->where('brand_id', '=', $brandId)->orWhere('brand_id');
        }

        return $query->get();
    }

    /**
     * @return Action[]|\Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachedActions()
    {
        return $this->hasMany(Action::class, 'component_id', 'id');
    }

    /**
     * @return Brands[]|\Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function brands()
    {
        return $this->hasMany(Brands::class, 'component_id', 'id');
    }

    /**
     * @return PermissionModel[]|\Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permission()
    {
        return $this->hasMany(PermissionModel::class, 'id', 'id');
    }

    /**
     * @return Component|\Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function component()
    {
        return $this->hasOne(Component::class, 'component_id', 'id');
    }

    /**
     * @param string $permissions
     * @return array
     */
    protected function mapPermissionsToArray($permissions = null)
    {
        if ($permissions === null || $permissions === '') {
            return [];
        }

        $exploded = explode(';', $permissions);
        $maps     = [];

        foreach ($exploded as $current) {
            if($current === '') {
                continue;
            }

            $permission = explode('=', $current);
            $maps[]     = [$permission[0] => (bool) $permission[1]];
        }

        $return = [];

        array_walk($maps, function($item) use(&$return) {
            $return[key($item)] = current($item);
        });

        return $return;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isCoreComponent($name)
    {
        return $name === 'core';
    }

    /**
     * cache prefix getter
     *
     * @return String
     */
    protected static function getCachePrefix()
    {
        return config('antares/memory::permission.cache_prefix');
    }

    /**
     * Returns all permissions as array.
     *
     * @param null $brandId
     * @return array
     */
    public function getAll($brandId = null)
    {
        $models     = Permission::fetchAll($brandId);
        $roles      = Role::query()->pluck('name', 'id')->toArray();
        $actions    = Action::query()->pluck('name', 'id')->toArray();

        $data = [
            'components' => [],
            'roles'     => $roles,
            'actions'   => $actions,
        ];

        /* @var $model Permission */
        foreach ($models as $model) {
            $name = $model->vendor . '/' . $model->name;
            $data['components'][$name] = $this->mapPermissionsToArray($model->permissions);
        }

        return $data;
    }

    /**
     * @param string $vendorName
     * @param string $packageName
     * @return Permission|null
     */
    public static function findByVendorAndName(string $vendorName, string $packageName) {
        return Permission::where('vendor', $vendorName)->where('name', $packageName)->first();
    }

    public function updateComponentPermissions(string $vendorName, string $packageName, array $componentActions, array $acl, $brandId = null) {
        try {
            DB::beginTransaction();

            $model = Permission::findByVendorAndName($vendorName, $packageName);

            if ($model === null) {
                return false;
            }

            foreach ($componentActions as $actionName) {
                $componentActionAttributes = [
                    'component_id' => $model->id,
                    'name' => $actionName
                ];

                /* @var $action Action */
                $action = $model->attachedActions()->where($componentActionAttributes)->first();

                if ($action === null) {
                    $action = $model->attachedActions()->getModel()->newInstance();
                    $action->fill($componentActionAttributes);
                }

                if (!$action->save()) {
                    throw new PermissionNotSavedException('Unable to update the component actions configuration.');
                }
            }

            $brands = ($brandId !== null) ? [$brandId] : $this->brands()->getModel()->pluck('id')->toArray();

            foreach ($acl as $rule => $isAllowed) {
                list($roleId, $actionId) = explode(':', $rule);

                foreach ($brands as $brand) {
                    $permissionModel = $this->permission()->getModel()
                        ->where('brand_id', '=', $brand)
                        ->where('action_id', '=', $actionId)
                        ->where('component_id', '=', $model->id)
                        ->where('role_id', '=', $roleId)
                        ->first();

                    $exists = $permissionModel === null ? false : $permissionModel->exists;

                    if ($exists) {
                        $permissionModel->allowed = (int)$isAllowed;
                    } else {
                        $permissionModel = $this->permission()->getModel()->newInstance()->fill([
                            'brand_id' => $brand,
                            'component_id' => $model->id,
                            'role_id' => $roleId,
                            'action_id' => $actionId,
                            'allowed' => (int)$isAllowed
                        ]);
                    }

                    if (!$permissionModel->save()) {
                        throw new PermissionNotSavedException('Unable to update the component permissions.');
                    }
                }
            }

            DB::commit();

            Cache::forget(self::getCachePrefix());
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::emergency($e);
            return false;
        }
    }

    /**
     * updates component permission settings
     * @param String $name
     * @param array | mixed $values
     * @param boolean $isNew
     */
    public function updatePermissions($name, $values, $isNew = false, $brandId = null)
    {
        try {
            if ($name === null) {
                return false;
            }

            if(str_contains($name, '/')) {
                list($vendorName, $packageName) = explode('/', $name);

                $model = Permission::findByVendorAndName($vendorName, $packageName);
            }
            else {
                $model = Permission::where('name', '=', $name)->first();
            }


            $actions = [];
            foreach ($values['actions'] as $actionName) {
                $action = $model->attachedActions()->where(['component_id' => $model->id, 'name' => $actionName])->first();
                if (is_null($action)) {
                    $action = $model->attachedActions()->getModel()->newInstance();
                }
                $action->fill(['component_id' => $model->id, 'name' => $actionName]);
                if (!$action->save()) {
                    throw new PermissionNotSavedException('Unable update module action configuration');
                }
                $actions[$action->id] = $action->name;
            }
            $brands = !is_null($brandId) ? [$brandId] : $this->brands()->getModel()->lists('id')->toArray();
            foreach ($values['acl'] as $rule => $isAllowed) {
                $rules    = explode(':', $rule);
                $roleId   = $rules[0];
                $actionId = array_search($values['actions'][$rules[1]], $actions);
                foreach ($brands as $brand) {
                    $permissionModel = $this->permission()->getModel()
                        ->where('brand_id', '=', $brand)
                        ->where('action_id', '=', $actionId)
                        ->where('component_id', '=', $model->id)
                        ->where('role_id', '=', $roleId)
                        ->get()
                        ->first();
                    $exists = (is_null($permissionModel)) ? false : $permissionModel->exists;
                    if ($exists) {
                        $permissionModel->allowed = (int) $isAllowed;
                    } else {
                        $permissionModel = $this->permission()->getModel()->newInstance()->fill([
                            'brand_id'     => $brand,
                            'component_id' => $model->id,
                            'role_id'      => $roleId,
                            'action_id'    => $actionId,
                            'allowed'      => (int) $isAllowed
                        ]);
                    }
                    if (!$permissionModel->save()) {
                        throw new PermissionNotSavedException('Unable update module permission');
                    }
                }
            }
            Cache::forget($this->getCachePrefix());
            return true;
        } catch (Exception $e) {
            Log::emergency($e);
            return false;
        }
    }

}
