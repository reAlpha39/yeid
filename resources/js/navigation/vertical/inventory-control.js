export default [
  {
    title: 'Inventory Control',
    icon: { icon: 'tabler-building-warehouse' },
    children: [
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
