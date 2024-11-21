export default [
  { heading: 'Charts' },
  {
    title: 'Charts',
    icon: { icon: 'tabler-chart-donut-2' },
    children: [
      {
        title: 'Apex Chart', to: 'charts-apex-chart', action: 'view',
        subject: 'user', },
      {
        title: 'Chartjs', to: 'charts-chartjs', action: 'view',
        subject: 'user', },
    ],
  },
]
