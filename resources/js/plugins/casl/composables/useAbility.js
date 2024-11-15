import { useAbility } from '@casl/vue';

export const usePermissions = () => {
    const { can: originalCan } = useAbility();

    const can = (action, subject) => {
        return originalCan(action, subject);
    };

    const hasAnyPermission = (subject, actions = ['view', 'create', 'update', 'delete']) => {
        return actions.some(action => can(action, subject));
    };

    return {
        can,
        hasAnyPermission,
    };
};
