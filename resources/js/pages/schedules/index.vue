<script setup>
// Previous script code remains exactly the same as in your paste.txt
import { ref } from "vue";

const months = [
  "JANUARI",
  "FEBRUARI",
  "MARET",
  "APRIL",
  "MEI",
  "JUNI",
  "JULI",
  "AGUSTUS",
  "SEPTEMBER",
  "OKTOBER",
  "NOVEMBER",
  "DESEMBER",
];

const weekHeaders = ["I", "II", "III", "IV"];

const scheduleData = ref([
  {
    title: "Schedule Change T-belt",
    shop: "Ign Coil",
    items: [
      {
        name: "Winding Secondary Line 3 # No 1",
        progress: "63%",
        time: "1x/6month",
        ct: 180,
        mp: 2,
        schedule: Array(48).fill("pending"),
      },
    ],
  },
  {
    title: "Timing Belt Winding",
    shop: "Ign Coil",
    items: Array(4)
      .fill()
      .map(() => ({
        name: "Winding Secondary Line 3 # No 2",
        progress: "63%",
        time: "1x/6month",
        ct: 180,
        mp: 2,
        schedule: ["completed", ...Array(47).fill("pending")],
      })),
  },
]);

const getStatusSymbol = (status) => {
  if (status === "completed") return "●";
  if (status === "inProgress") return "▲";
  return "△";
};

const isOddWeek = (index) => {
  return index % 2 === 0;
};
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

  <VCard class="mb-6 pa-6">
    <div class="d-flex flex-wrap gap-4 mt-2 mb-6">
      <VSpacer />
      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <VBtn
          variant="tonal"
          prepend-icon="tabler-upload"
          @click="handleExport"
          :loading="loadingExport"
        >
          Export
        </VBtn>

        <VBtn
          v-if="$can('create', 'pressShot')"
          prepend-icon="tabler-plus"
          to="schedules/add"
        >
          Add New Schedule
        </VBtn>
      </div>
    </div>

    <VDivider class="my-6" />

    <div v-for="(section, index) in scheduleData" :key="index">
      <VCard class="mb-4" variant="outlined" style="background-color: #f9f9f9">
        <VCardTitle class="d-flex justify-space-between align-center pa-4">
          <div>
            {{ section.title }}
            <div class="text-subtitle-2 text-grey">
              Shop: {{ section.shop }}
            </div>
          </div>

          <div class="text-caption">PIC: MTC</div>
        </VCardTitle>

        <div class="table-wrapper">
          <VTable>
            <thead>
              <tr class="header-row">
                <th v-if="index === 1" class="item-column" rowspan="2">ITEM</th>
                <th rowspan="2">TIME</th>
                <th rowspan="2">CT</th>
                <th rowspan="2">MP</th>
                <template v-for="month in months" :key="month">
                  <th :colspan="4" class="text-center month-header">
                    {{ month }}
                  </th>
                </template>
              </tr>
              <tr class="header-row">
                <template v-for="month in months" :key="`week-${month}`">
                  <th
                    v-for="(week, weekIndex) in weekHeaders"
                    :key="`${month}-${week}`"
                    class="text-center week-header"
                    :class="{ 'week-odd': isOddWeek(weekIndex) }"
                  >
                    {{ week }}
                  </th>
                </template>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, itemIndex) in section.items" :key="itemIndex">
                <td v-if="index === 1" class="item-column">
                  {{ item.name }}
                  <VChip size="small" color="grey-lighten-3" class="mt-1">
                    {{ item.progress }}
                  </VChip>
                </td>
                <td>{{ item.time }}</td>
                <td>{{ item.ct }}</td>
                <td>{{ item.mp }}</td>
                <template
                  v-for="(status, statusIndex) in item.schedule"
                  :key="statusIndex"
                >
                  <td class="text-center status-symbol">
                    {{ getStatusSymbol(status) }}
                  </td>
                </template>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCard>
    </div>
  </VCard>
</template>

<style scoped>
.table-wrapper {
  overflow-x: auto;
  position: relative;
}

.v-table {
  border: 1px solid #eee;
  border-collapse: collapse; /* Ensures borders don't double up */
}

.v-table th,
.v-table td {
  border: 1px solid #dbdade; /* Add borders to all cells */
}

.v-table thead tr th {
  height: 12px;
  padding: 2px 8px;
  font-size: 12px;
  line-height: 1;
}

.header-row th {
  height: 12px;
}

.v-table td {
  padding: 8px;
}

.item-column {
  min-width: 300px;
  max-width: 300px;
}

.time-column {
  min-width: 100px;
}

.fixed-column {
  position: sticky;
  left: 0;
  background-color: #fff;
  z-index: 1;
}

.fixed-column:nth-child(4) {
  border-right: 2px solid #eee;
}

th.fixed-column {
  background-color: #f5f5f5;
  z-index: 2;
}

.month-header {
  border-bottom: 1px solid #dbdade;
  min-width: 120px;
  height: 12px;
  padding: 2px 8px;
}

.week-header {
  min-width: 30px;
  height: 12px;
  padding: 2px 8px;
}

.week-header.week-odd {
  background-color: #feecec;
}

.week-header:not(.week-odd) {
  background-color: #f9d8d8;
}

.status-symbol {
  font-size: 14px;
}

/* Ensure borders are visible on fixed columns */
.fixed-column {
  border-right: 1px solid #dbdade !important;
}
</style>
s
