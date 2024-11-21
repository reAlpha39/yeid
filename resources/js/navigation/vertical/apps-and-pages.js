export default [
  { heading: 'Apps & Pages' },
  {
    title: 'Ecommerce',
    icon: { icon: 'tabler-shopping-cart' },
    children: [
      {
        title: 'Dashboard',
        to: 'apps-ecommerce-dashboard',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'Product',
        children: [
          {
            title: 'List', to: 'apps-ecommerce-product-list', action: 'view',
            subject: 'user',
          },
          {
            title: 'Add', to: 'apps-ecommerce-product-add', action: 'view',
            subject: 'user',
          },
          {
            title: 'Category', to: 'apps-ecommerce-product-category-list', action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Order',
        children: [
          {
            title: 'List', to: 'apps-ecommerce-order-list', action: 'view',
            subject: 'user',
          },
          {
            title: 'Details', to: { name: 'apps-ecommerce-order-details-id', params: { id: '9042' } }, action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Customer',
        children: [
          {
            title: 'List', to: 'apps-ecommerce-customer-list', action: 'view',
            subject: 'user',
          },
          {
            title: 'Details', to: { name: 'apps-ecommerce-customer-details-id', params: { id: 478426 } }, action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Manage Review',
        to: 'apps-ecommerce-manage-review',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'Referrals',
        to: 'apps-ecommerce-referrals',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'Settings',
        to: 'apps-ecommerce-settings',
        action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Academy',
    icon: { icon: 'tabler-school' },
    children: [
      {
        title: 'Dashboard', to: 'apps-academy-dashboard', action: 'view',
        subject: 'user',
      },
      {
        title: 'My Course', to: 'apps-academy-my-course', action: 'view',
        subject: 'user',
      },
      {
        title: 'Course Details', to: 'apps-academy-course-details', action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Logistics',
    icon: { icon: 'tabler-truck' },
    children: [
      {
        title: 'Dashboard', to: 'apps-logistics-dashboard', action: 'view',
        subject: 'user',
      },
      {
        title: 'Fleet', to: 'apps-logistics-fleet', action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Email',
    icon: { icon: 'tabler-mail' },
    to: 'apps-email',
    action: 'view',
    subject: 'user',
  },
  {
    title: 'Chat',
    icon: { icon: 'tabler-message-circle-2' },
    to: 'apps-chat',
    action: 'view',
    subject: 'user',
  },
  {
    title: 'Calendar',
    icon: { icon: 'tabler-calendar' },
    to: 'apps-calendar',
    action: 'view',
    subject: 'user',
  },
  {
    title: 'Invoice',
    icon: { icon: 'tabler-file-invoice' },
    children: [
      {
        title: 'List', to: 'apps-invoice-list', action: 'view',
        subject: 'user',
      },
      {
        title: 'Preview', to: { name: 'apps-invoice-preview-id', params: { id: '5036' } }, action: 'view',
        subject: 'user',
      },
      {
        title: 'Edit', to: { name: 'apps-invoice-edit-id', params: { id: '5036' } }, action: 'view',
        subject: 'user',
      },
      {
        title: 'Add', to: 'apps-invoice-add', action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'User',
    icon: { icon: 'tabler-user' },

    children: [
      {
        title: 'List', to: 'apps-user-list', action: 'view',
        subject: 'user',
      },
      {
        title: 'View', to: { name: 'apps-user-view-id', params: { id: 21 } }, action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Roles & Permissions',
    icon: { icon: 'tabler-lock' },
    children: [
      {
        title: 'Roles', to: 'apps-roles', action: 'view',
        subject: 'user',
      },
      {
        title: 'Permissions', to: 'apps-permissions', action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Pages',
    icon: { icon: 'tabler-file' },
    children: [
      {
        title: 'User Profile', to: { name: 'pages-user-profile-tab', params: { tab: 'profile' } }, action: 'view',
        subject: 'user',
      },
      {
        title: 'Account Settings', to: { name: 'pages-account-settings-tab', params: { tab: 'account' } }, action: 'view',
        subject: 'user',
      },
      {
        title: 'Pricing', to: 'pages-pricing', action: 'view',
        subject: 'user',
      },
      {
        title: 'FAQ', to: 'pages-faq', action: 'view',
        subject: 'user',
      },
      {
        title: 'Miscellaneous',
        children: [
          {
            title: 'Coming Soon', to: 'pages-misc-coming-soon', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Under Maintenance', to: 'pages-misc-under-maintenance', target: '_blank', action: 'view',
            subject: 'user',
          },
          { title: 'Page Not Found - 404', to: { path: '/pages/misc/not-found' }, target: '_blank' },
          { title: 'Not Authorized - 401', to: { path: '/pages/misc/not-authorized' }, target: '_blank' },
        ],
      },
    ],
  },
  {
    title: 'Authentication',
    icon: { icon: 'tabler-shield-lock' },
    children: [
      {
        title: 'Login',
        children: [
          {
            title: 'Login v1', to: 'pages-authentication-login-v1', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Login v2', to: 'pages-authentication-login-v2', target: '_blank', action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Register',
        children: [
          {
            title: 'Register v1', to: 'pages-authentication-register-v1', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Register v2', to: 'pages-authentication-register-v2', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Register Multi-Steps', to: 'pages-authentication-register-multi-steps', target: '_blank', action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Verify Email',
        children: [
          {
            title: 'Verify Email v1', to: 'pages-authentication-verify-email-v1', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Verify Email v2', to: 'pages-authentication-verify-email-v2', target: '_blank', action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Forgot Password',
        children: [
          {
            title: 'Forgot Password v1', to: 'pages-authentication-forgot-password-v1', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Forgot Password v2', to: 'pages-authentication-forgot-password-v2', target: '_blank', action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Reset Password',
        children: [
          {
            title: 'Reset Password v1', to: 'pages-authentication-reset-password-v1', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Reset Password v2', to: 'pages-authentication-reset-password-v2', target: '_blank', action: 'view',
            subject: 'user',
          },
        ],
      },
      {
        title: 'Two Steps',
        children: [
          {
            title: 'Two Steps v1', to: 'pages-authentication-two-steps-v1', target: '_blank', action: 'view',
            subject: 'user',
          },
          {
            title: 'Two Steps v2', to: 'pages-authentication-two-steps-v2', target: '_blank', action: 'view',
            subject: 'user',
          },
        ],
      },
    ],
  },
  {
    title: 'Wizard Examples',
    icon: { icon: 'tabler-dots' },
    children: [
      {
        title: 'Checkout', to: { name: 'wizard-examples-checkout' }, action: 'view',
        subject: 'user',
      },
      {
        title: 'Property Listing', to: { name: 'wizard-examples-property-listing' }, action: 'view',
        subject: 'user',
      },
      {
        title: 'Create Deal', to: { name: 'wizard-examples-create-deal' }, action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Dialog Examples',
    icon: { icon: 'tabler-square' },
    to: 'pages-dialog-examples',
    action: 'view',
    subject: 'user',
  },
]
