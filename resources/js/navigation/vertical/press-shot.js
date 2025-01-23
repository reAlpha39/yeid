export default [
  {
    title: 'Press Shot',
    icon: { icon: 'tabler-stack-3' },
    children: [
      {
        title: 'Part List',
        to: 'press-shot-part-list',
        subject: 'pressShotPartList',
        action: 'view',
      },
      {
        title: 'Exchange Data',
        to: 'press-shot-exchange-data',
        subject: 'pressShotExcData',
        action: 'view',
      },
      {
        title: 'Production Data',
        to: 'press-shot-production-data',
        subject: 'pressShotProdData',
        action: 'view',
      },
      {
        title: 'Master Part',
        to: 'press-shot-master-part',
        subject: 'pressShotMasterPart',
        action: 'view',
      },
      {
        title: 'History Activity',
        to: 'press-shot-history-activity',
        subject: 'pressShotHistoryAct',
        action: 'view',
      },
    ],
  },
]
