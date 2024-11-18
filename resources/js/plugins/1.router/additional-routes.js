import { useAuthStore } from '@/stores/auth'

const emailRouteComponent = () => import('@/pages/apps/email/index.vue')

// 👉 Redirects
export const redirects = [
  {
    path: '/',
    name: 'index',
    redirect: to => {
      const authStore = useAuthStore()

      // If not authenticated, redirect to login
      if (!authStore.isAuthenticated) {
        return { name: 'login', query: to.query }
      }

      // Get user role from store
      const userRole = authStore.userData?.role_access

      console.log('Redirect - User Role:', userRole)

      // Define redirects based on role
      switch (userRole) {
        case '3':
        case '2':
        case '1':
          return { name: 'dashboards-crm' }
        default:
          console.warn('Unknown user role:', userRole)
          return { name: 'login', query: to.query }
      }
    },
  },
]

export const routes = [
  // Email filter
  {
    path: '/apps/email/filter/:filter',
    name: 'apps-email-filter',
    component: emailRouteComponent,
    meta: {
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  // Email label
  {
    path: '/apps/email/label/:label',
    name: 'apps-email-label',
    component: emailRouteComponent,
    meta: {
      // contentClass: 'email-application',
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },
  {
    path: '/dashboards/logistics',
    name: 'dashboards-logistics',
    component: () => import('@/pages/apps/logistics/dashboard.vue'),
  },
  {
    path: '/dashboards/academy',
    name: 'dashboards-academy',
    meta: {
      action: 'view',
      subject: 'part',
    },
    component: () => import('@/pages/apps/academy/dashboard.vue'),
  },
  {
    path: '/apps/ecommerce/dashboard',
    name: 'apps-ecommerce-dashboard',
    component: () => import('@/pages/dashboards/ecommerce.vue'),
  },
]
