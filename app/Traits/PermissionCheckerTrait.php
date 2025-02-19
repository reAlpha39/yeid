<?php

namespace App\Traits;

use App\Models\MasUser;
use Exception;

trait PermissionCheckerTrait
{
    protected function checkAccess($modules, $actions)
    {
        try {
            $user = MasUser::findOrFail(auth()->user()->id);
            $controlAccess = json_decode($user->control_access, true);

            // Convert single module/action to arrays for consistent handling
            $moduleArray = is_array($modules) ? $modules : [$modules];
            $actionArray = is_array($actions) ? $actions : [$actions];

            // Check if any of the modules grant permission for any of the actions
            foreach ($moduleArray as $module) {
                if (!$controlAccess || !isset($controlAccess[$module])) {
                    continue;
                }

                foreach ($actionArray as $action) {
                    // Check if the action exists and is allowed
                    if (isset($controlAccess[$module][$action]) && $controlAccess[$module][$action]) {
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function unauthorizedResponse()
    {
        return response()->json([
            'success' => false,
            'not_authorized' => true,
            'message' => 'Unauthorized access'
        ], 403);
    }
}
