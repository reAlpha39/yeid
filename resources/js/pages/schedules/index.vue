<script setup>
import { onMounted, ref } from "vue";

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
const currentYear = new Date().getFullYear();
const data = ref([]);

// Transform API data into the required format
function transformApiData(apiData) {
  return apiData
    .map((activity) => ({
      title: activity.activity_name,
      shop: activity.shop_id,
      pic: activity.pic?.name,
      items: activity.tasks.map((task) => {
        // Create a 48-week schedule array with empty strings
        const schedule = Array(48).fill("");

        // Calculate progress based on completed executions
        const totalExecutions = task.executions.length;
        const completedExecutions = task.executions.filter(
          (execution) => execution.status === "completed"
        ).length;

        const progress =
          totalExecutions > 0
            ? `${Math.round((completedExecutions / totalExecutions) * 100)}%`
            : "0%";

        // Mark scheduled weeks
        task.executions.forEach((execution) => {
          const weekIndex = execution.scheduled_week - 1;
          if (weekIndex >= 0 && weekIndex < 48) {
            schedule[weekIndex] = execution.status || "pending";
          }
        });

        return {
          name: task.machine?.machinename + " Line " + task.machine?.plantcode,
          progress, // You might want to calculate this based on completed executions
          time: `${task.frequency_times}x/${task.frequency_period}`,
          ct: task.cycle_time,
          mp: task.manpower_srequired,
          schedule: schedule,
        };
      }),
    }))
    .filter((activity) => activity.items.length > 0); // Only show activities with tasks
}

function getStatusSymbol(status) {
  if (!status) return "\u00A0"; // Return empty string for weeks without scheduled tasks
  if (status === "completed") return "●";
  if (status === "inProgress") return "▲";
  if (status === "pending") return "△";
  return "\u00A0"; // Return empty string for any unknown status
}

function isOddWeek(index) {
  return index % 2 === 0;
}

const loadingExport = ref(false);
const scheduleData = ref([]);

async function fetchData() {
  try {
    const response = await $api("/schedule/activities/table", {
      params: {
        year: currentYear,
      },
    });

    scheduleData.value = transformApiData(response.data);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.error(err);
  }
}

async function handleExport() {
  loadingExport.value = true;
  try {
    // Implement export logic here
    await new Promise((resolve) => setTimeout(resolve, 1000));
  } catch (err) {
    toast.error("Export failed");
  } finally {
    loadingExport.value = false;
  }
}

function handleStatusClick(status, item) {
  if (status === "completed") {
    // Implement logic to show details of completed task
    console.log("Show details of completed task", item);
  } else {
    // Implement logic to update task status
    console.log("Update task status", item);
  }
}

onMounted(() => {
  fetchData();
});
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

          <div class="text-caption">PIC: {{ section.pic }}</div>
        </VCardTitle>

        <div class="table-wrapper">
          <VTable>
            <thead>
              <tr class="header-row">
                <th class="item-column" rowspan="2">ITEM</th>
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
                <td class="item-column">
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
                    <span
                      class="clickable-status"
                      @click="handleStatusClick(status, item)"
                      role="button"
                      tabindex="0"
                    >
                      {{ getStatusSymbol(status) }}
                    </span>
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
  border-collapse: collapse;
}

.v-table th,
.v-table td {
  border: 1px solid #dbdade;
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
  height: 100%;
  vertical-align: middle;
}

.clickable-status {
  cursor: pointer;
  user-select: none;
  padding: 2px;
  display: inline-block;
  font-size: 1.25rem;
  line-height: 1; /* This helps maintain consistent height */
  min-width: 1.25rem; /* Makes all cells same width */
  text-align: center;
}

.clickable-status:hover {
  background-color: rgba(0, 0, 0, 0.04);
  border-radius: 4px;
}

/* Optional: Add keyboard focus styles for accessibility */
.clickable-status:focus {
  outline: 2px solid #1976d2;
  outline-offset: 2px;
  border-radius: 4px;
}
</style>
