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
          title: 'Schedule',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VTabs v-model="currentTab" class="v-tabs-pill">
    <VTab>Graph</VTab>
    <VTab>Schedule</VTab>
  </VTabs>

  <VWindow v-model="currentTab" class="mt-6">
    <VWindowItem key="`window1`">
      <ScheduleGraph />
    </VWindowItem>
    <VWindowItem key="`window2`">
      <ScheduleTable />
    </VWindowItem>
  </VWindow>
</template>
