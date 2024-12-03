export default [
  {
    title: 'Inventory Control',
    icon: { icon: 'tabler-building-warehouse' },
    children: [
      {
        title: 'Part List',
        to: 'inventory-control-part-list',
        action: 'view',
        subject: 'inventoryInbound',
      },
      {
        title: 'Master Part',
        to: 'inventory-control-master-part',
        action: 'view',
        subject: 'inventoryInbound',
      },
      {
        title: 'Inventory In-Bound',
        to: 'inventory-control-inventory-inbound',
        action: 'view',
        subject: 'inventoryInbound',
      },
      {
        title: 'Inventory Out-Bound',
        to: 'inventory-control-inventory-outbound',
        action: 'view',
        subject: 'inventoryOutbound',
      },
    ],
  },
]
