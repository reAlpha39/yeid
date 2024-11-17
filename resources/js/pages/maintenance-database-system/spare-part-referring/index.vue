<script setup>
definePage({
  meta: {
    action: "view",
    subject: "maintenanceReport",
  },
});

const currentTab = ref("window1");
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Maintenance Database System',
          class: 'text-h4',
        },
        {
          title: 'Spare Part Referring',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VTabs v-model="currentTab" class="v-tabs-pill">
    <VTab class="pb-2">Control Const<br />Spare Parts</VTab>
    <VTab class="pb-2">Result Const of<br />Used Parts</VTab>
    <VTab class="pb-2">Check Running<br />Const of Machines</VTab>
    <VTab class="pb-2">Inventory Cost<br />Change List(Top 100)</VTab>
  </VTabs>

  <VWindow v-model="currentTab">
    <VWindowItem key="`window1`">
      <ControlConstSparePartsTab />
    </VWindowItem>
    <VWindowItem key="`window2`">
      <ResultConstOfUsedPartsTab />
    </VWindowItem>
    <VWindowItem key="`window3`">
      <CheckRunningConstOfMachinesTab />
    </VWindowItem>
    <VWindowItem key="`window4`">
      <InventoryConstChangeListTab />
    </VWindowItem>
  </VWindow>
</template>
