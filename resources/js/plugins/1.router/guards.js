import { useAuthStore } from '@/stores/auth';
import { useAbility } from '@casl/vue';
import { canNavigate } from '@layouts/plugins/casl';

/**
 * Check if route requires permissions check
 */
const requiresPermission = route => {
  return route.meta?.action && route.meta?.subject;
};

/**
 * Setup navigation guards
 */
export const setupGuards = router => {
  router.beforeEach(async (to) => {
    const authStore = useAuthStore()

    // Allow public routes without any checks
    if (to.meta.public)
      return;

    // Handle unauthenticated-only routes (like login page)
    if (to.meta.unauthenticatedOnly) {
      if (authStore.isAuthenticated)
        return '/';
      return undefined;
    }

    // If route requires authentication and user is not logged in
    if (!authStore.isAuthenticated) {
      return {
        name: 'login',
        query: {
          ...to.query,
          to: to.fullPath !== '/' ? to.path : undefined,
        },
      };
    }

    // Check permissions if route requires them
    if (to.matched.some(requiresPermission)) {
      if (!canNavigate(to)) {
        return { name: 'not-authorized' };
      }
    }

    // Load user permissions if they haven't been loaded yet
    const ability = useAbility();
    const storedRules = authStore.getAbilityRules;

    if (authStore.isAuthenticated && storedRules && !ability.rules.length) {
      ability.update(storedRules);
    }

    return undefined;
  });

  // Optional: Add afterEach hook for analytics or other post-navigation tasks
  router.afterEach((to) => {
    // Reset scroll position, update page title, etc.
    window.scrollTo(0, 0);

    // Update document title if meta title is set
    if (to.meta.title) {
      document.title = to.meta.title;
    }
  });
};
