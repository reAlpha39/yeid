<script setup>
import { ref } from "vue";
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
const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth();

const isUpdateScheduleTaskDialogVisible = ref(false);
const selectedUpdateTaskExecutionId = ref(null);

const isLoading = ref(false);
const data = ref([]);
const years = ref([]);
// const departments = ref([]);
// const shops = ref([]);
// const machines = ref([]);

const selectedShop = ref();
const selectedDepartment = ref();
const selectedMachine = ref();
const year = ref(currentYear);
const month = ref(months[currentMonth]);

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}

async function fetchData() {
  try {
    isLoading.value = true;
    const response = await $api("/schedule/activities/table", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
      params: {
        year: year.value,
        month: months.indexOf(month.value) + 1,
        shop: selectedShop.value?.shopcode,
        department: selectedDepartment.value?.id,
        machine: selectedMachine.value?.machineno,
      },
    });
    data.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    isLoading.value = false;
  }
}

const flattenedData = computed(() => {
  const flattened = [];
  data.value.forEach((activity) => {
    activity.tasks.forEach((task) => {
      task.executions.forEach((execution) => {
        flattened.push({
          activity_name: activity.activity_name,
          shop_id: activity.shop_id,
          shop_name: task.machine?.shopname,
          machine_no: task.machine?.machineno,
          machine_name: task.machine?.machinename,
          plantcode: task.machine?.plantcode,
          item_id: execution.item_id,
          scheduled_week: execution.scheduled_week,
          scheduled_month: Math.ceil(execution.scheduled_week / 4),
          week_in_month: ((execution.scheduled_week - 1) % 4) + 1,
          pic_name: activity.pic?.name,
          status: execution.status,
          completion_week: execution.completion_week,
        });
      });
    });
  });
  return flattened;
});

function openEditPage(item) {
  console.log(item.execution_id);
  selectedUpdateTaskExecutionId.value = item.item_id;
  isUpdateScheduleTaskDialogVisible.value = true;
}

const headers = [
  {
    title: "ACTIVITY",
    key: "activity_name",
  },
  {
    title: "SHOP",
    key: "shop_id",
  },
  {
    title: "ITEM",
    key: "machine_no",
  },
  {
    title: "LINE",
    key: "plantcode",
  },
  {
    title: "SCHEDULE",
    key: "scheduled_week",
  },
  {
    title: "PIC",
    key: "pic_name",
  },
  // {
  //   title: "STATUS",
  //   key: "status",
  // },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

const sortBy = ref([{ key: "scheduled_week", order: "asc" }]);

watch([year, month, selectedDepartment, selectedShop, selectedMachine], () => {
  fetchData();
});

onMounted(() => {
  fetchData();
  getLastTenYears();
});
</script>

<template>
  <VCard class="mb-6 pa-6">
    <div class="d-flex flex-wrap gap-4 mt-2 mb-6">
      <VSpacer />
      <div style="inline-size: 7rem">
        <AppAutocomplete v-model="year" :items="years" outlined />
      </div>
      <div style="inline-size: 12rem">
        <AppAutocomplete v-model="month" :items="months" outlined />
      </div>

      <!-- <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <VBtn
          variant="tonal"
          prepend-icon="tabler-upload"
          @click="handleExport"
          :loading="loadingExport"
        >
          Export
        </VBtn>
      </div> -->
    </div>
    <!-- <VRow>
      <VCol cols="3">
        <VAutocomplete
          v-model="selectedDepartment"
          placeholder="PIC"
          item-title="title"
          :items="departments"
          return-object
          outlined
          clearable
        />
      </VCol>
      <VCol cols="3">
        <VAutocomplete
          v-model="selectedShop"
          placeholder="Shop"
          item-title="title"
          :items="shops"
          return-object
          outlined
          clearable
        />
      </VCol>
      <VCol cols="3">
        <VAutocomplete
          v-model="selectedMachine"
          placeholder="Machine"
          item-title="title"
          :items="machines"
          return-object
          outlined
          clearable
        />
      </VCol>
    </VRow> -->

    <VDivider class="mt-4" />

    <div class="sticky-actions-wrapper">
      <VDataTable
        :items="flattenedData"
        :headers="headers"
        :loading="isLoading"
        :sort-by="sortBy"
        class="text-no-wrap"
        height="562"
      >
        <template #item.shop_id="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.shop_name }}</span
              >
              <small>{{ item.shop_id }}</small>
            </div>
          </div>
        </template>

        <template #item.machine_no="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.machine_name }}</span
              >
              <small>{{ item.machine_no }}</small>
            </div>
          </div>
        </template>

        <template #item.scheduled_week="{ item }">
          {{ weekOfYear[item.scheduled_week - 1].title }}
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <!-- <VChip
              :color="
                item.status === 'completed'
                  ? 'success'
                  : item.status === 'pending'
                  ? 'warning'
                  : 'error'
              "
              size="small"
              >{{ item.status }}</VChip
            > -->

            <div
              class="status-indicator mx-2"
              :class="{
                'status-green': item.status === 'completed',
                'status-orange': item.status === 'overdue',
                'status-red': item.status === 'pending',
              }"
            />

            <IconBtn @click="openEditPage(item)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </div>
  </VCard>

  <UpdateScheduleTask
    v-model:isDialogVisible="isUpdateScheduleTaskDialogVisible"
    v-model:id="selectedUpdateTaskExecutionId"
    @submit="fetchData"
  />
</template>

<style>
.status-indicator {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  margin: auto;
}

.status-green {
  background-color: #4caf50;
}

.status-orange {
  background-color: #f87d02;
}

.status-red {
  background-color: #fa0202;
}
</style>
