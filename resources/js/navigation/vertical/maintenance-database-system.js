export default [
  {
    title: 'Maintenance DB System',
    icon: { icon: 'tabler-database' },
    children: [
      {
        title: 'Department Request',
        to: 'maintenance-database-system-department-request',
        subject: 'mtDbsDeptReq',
        action: 'view',
      },
      {
        title: 'Maintenance Report',
        to: 'maintenance-database-system-maintenance-report',
        subject: 'mtDbsMtReport',
        action: 'view',
      },
      {
        title: 'Request to Workshop',
        to: 'maintenance-database-system-request-to-workshop',
        subject: 'mtDbsReqWork',
        action: 'view',
      },
      {
        title: 'Maintenance Data Analyzation',
        to: 'maintenance-database-system-maintenance-data-analyzation',
        subject: 'mtDbsDbAnl',
        action: 'view',
      },
      {
        title: 'Spare Part Referring',
        to: 'maintenance-database-system-spare-part-referring',
        subject: 'mtDbsSparePart',
        action: 'view',
      },
    ],
  },
]
