import { useAbility } from '@casl/vue';
import { canNavigate } from '@layouts/plugins/casl';

/**
 * Check if route requires permissions check
 */
const requiresPermission = route => {
  return route.meta?.action && route.meta?.subject;
};

/**
 * Check user authentication status
 */
const isAuthenticated = () => {
  return !!(useCookie('userData').value && useCookie('accessToken').value);
};

/**
 * Setup navigation guards
 */
export const setupGuards = router => {
  router.beforeEach(async (to) => {
    // Allow public routes without any checks
    if (to.meta.public)
      return;

    const isLoggedIn = isAuthenticated();

    // Handle unauthenticated-only routes (like login page)
    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/';
      return undefined;
    }

    // If route requires authentication and user is not logged in
    if (!isLoggedIn) {
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
    const storedRules = useCookie('userAbilityRules').value;

    if (isLoggedIn && storedRules && !ability.rules.length) {
      ability.update(storedRules);
    }

    console.log('aaa ' + to.meta.subject)

    // Check if the route is pressShot related
    if (to.meta.subject === 'pressShot') {
      try {

        await $api('/log-activity', {
          method: 'POST',
          body: {
            page: to.name || to.path,
            action: 'pressShot_page_visit',
            description: `Accessed PressShot page: ${to.name || to.path}`
          }
        })

      } catch (error) {
        console.error('Failed to log activity:', error)
      }
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
