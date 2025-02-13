<?php

namespace App\Traits;

use App\Models\MasUser;
use Exception;

trait PermissionCheckerTrait
{
    protected function checkAccess($modules, $action)
    {
        try {
            $user = MasUser::findOrFail(auth()->user()->id);
            $controlAccess = json_decode($user->control_access, true);

            // Convert single module to array for consistent handling
            $moduleArray = is_array($modules) ? $modules : [$modules];

            // Check if any of the modules grant permission
            foreach ($moduleArray as $module) {
                if (!$controlAccess || !isset($controlAccess[$module])) {
                    continue;
                }

                switch ($action) {
                    case 'view':
                        if ($controlAccess[$module]['view'] ?? false) return true;
                        break;
                    case 'create':
                        if ($controlAccess[$module]['create'] ?? false) return true;
                        break;
                    case 'update':
                        if ($controlAccess[$module]['update'] ?? false) return true;
                        break;
                    case 'delete':
                        if ($controlAccess[$module]['delete'] ?? false) return true;
                        break;
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
            'message' => 'Unauthorized access'
        ], 403);
    }
}
