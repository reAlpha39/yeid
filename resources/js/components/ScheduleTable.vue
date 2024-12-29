<script setup>
import { onMounted, ref } from "vue";
import { useToast } from "vue-toastification";

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

const toast = useToast();

const isLoading = ref(false);

const weekHeaders = ["I", "II", "III", "IV"];
const currentYear = new Date().getFullYear();
const data = ref([]);

const selectedUpdateTaskExecutionId = ref(null);
const selectedCreateTask = ref(null);
const selectedWeekId = ref(null);
const isUpdateScheduleTaskDialogVisible = ref(false);
const isCreateScheduleTaskDialogVisible = ref(false);
const isDeleteDialogVisible = ref(false);

const itemToDelete = ref(null);

// Transform API data into the required format
function transformApiData(apiData) {
  return apiData
    .map((activity) => ({
      title: activity.activity_name,
      shop: activity.shop_id,
      pic: activity.pic?.name,
      items: activity.tasks.map((task) => {
        // Create a 48-week schedule array with empty strings
        const schedule = Array(48)
          .fill(null)
          .map((_, index) => ({
            week_index: index + 1,
            item_id: null,
            status: "",
          }));

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
            schedule[weekIndex] = {
              item_id: execution.item_id,
              status: execution.status || "pending",
            };
          }
        });

        return {
          task_id: task.task_id,
          name: task.machine?.machinename + " Line " + task.machine?.plantcode,
          progress, // You might want to calculate this based on completed executions
          time: `${task.frequency_times}x/${task.frequency_period}`,
          ct: task.cycle_time,
          mp: task.manpower_required,
          schedule: schedule,
        };
      }),
    }))
    .filter((activity) => activity.items.length > 0); // Only show activities with tasks
}

function getStatusSymbol(status) {
  if (!status) return "\u00A0"; // Return empty string for weeks without scheduled tasks
  if (status === "completed") return "●";
  if (status === "overdue") return "▲";
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
    isLoading.value = true;

    const response = await $api("/schedule/activities/table", {
      params: {
        year: currentYear,
      },
    });

    data.value = response.data;
    scheduleData.value = transformApiData(response.data);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.error(err);
  } finally {
    isLoading.value = false;
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

async function deleteScheduleItem() {
  try {
    const result = await $api("/schedule/tasks/" + itemToDelete.value.task_id, {
      method: "DELETE",

      onResponseError({ response }) {
        toast.error(response._data.message);
        // errors.value = response._data.errors;
      },
    });

    itemToDelete.value = null;
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    itemToDelete.value = null;
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

function openDeleteDialog(item) {
  itemToDelete.value = item;
  isDeleteDialogVisible.value = true;
}

function handleStatusClick(execution, task) {
  if (execution.item_id === null) {
    selectedCreateTask.value = task;
    selectedWeekId.value = execution.week_index;
    isCreateScheduleTaskDialogVisible.value = true;
  } else {
    selectedUpdateTaskExecutionId.value = execution.item_id;
    isUpdateScheduleTaskDialogVisible.value = true;
  }
}

onMounted(() => {
  fetchData();
});
</script>

<template>
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

    <div
      v-if="isLoading"
      class="d-flex flex-column align-center justify-center my-8"
    >
      <VProgressCircular
        indeterminate
        color="primary"
        size="48"
        width="4"
        class="mb-2"
      />
      <VCardText class="text-center text-body-1 text-medium-emphasis">
        Loading data, please wait...
      </VCardText>
    </div>

    <div v-else v-for="(section, index) in scheduleData" :key="index">
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
                <td
                  class="item-column d-flex justify-space-between align-center"
                >
                  <div>
                    {{ item.name }}
                    <VChip size="small" color="grey-lighten-3" class="mt-1">
                      {{ item.progress }}
                    </VChip>
                  </div>
                  <IconBtn @click="openDeleteDialog(item)" color="error">
                    <VIcon icon="tabler-trash" color="error" />
                  </IconBtn>
                </td>
                <td>{{ item.time }}</td>
                <td>{{ item.ct }}</td>
                <td>{{ item.mp }}</td>
                <template
                  v-for="(execution, statusIndex) in item.schedule"
                  :key="statusIndex"
                >
                  <td class="text-center status-symbol">
                    <span
                      class="clickable-status"
                      @click="handleStatusClick(execution, item)"
                      role="button"
                      tabindex="0"
                    >
                      {{ getStatusSymbol(execution.status) }}
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

  <UpdateScheduleTask
    v-model:isDialogVisible="isUpdateScheduleTaskDialogVisible"
    v-model:id="selectedUpdateTaskExecutionId"
    @submit="fetchData"
  />

  <AddScheduleTask
    v-model:isDialogVisible="isCreateScheduleTaskDialogVisible"
    v-model:task="selectedCreateTask"
    v-model:weekId="selectedWeekId"
    @submit="fetchData"
  />

  <VDialog v-model="isDeleteDialogVisible" max-width="500px">
    <VCard class="pa-4">
      <VCardTitle class="text-center">
        Are you sure you want to delete this item?
      </VCardTitle>

      <VCardActions class="pt-4">
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="isDeleteDialogVisible = !isDeleteDialogVisible"
        >
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="deleteScheduleItem()">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
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
