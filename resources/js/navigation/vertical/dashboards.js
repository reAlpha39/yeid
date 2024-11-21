export default [
  {
    title: 'Dashboards',
    icon: { icon: 'tabler-smart-home' },
    children: [
      {
        title: 'Analytics',
        to: 'dashboards-analytics',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'CRM',
        to: 'dashboards-crm',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'Ecommerce',
        to: 'dashboards-ecommerce',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'Academy',
        to: 'dashboards-academy',
        action: 'view',
        subject: 'user',
      },
      {
        title: 'Logistics',
        to: 'dashboards-logistics',
        action: 'view',
        subject: 'user',
      },
    ],
    badgeContent: '5',
    badgeClass: 'bg-error',
  },
]
