<script setup>
import axios from "axios";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "maintenanceReport",
  },
});

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const isDetailDialogVisible = ref(false);

const selectedItem = ref("");
const searchQuery = ref("");

const now = new Date();
const formattedDate = new Intl.DateTimeFormat("en", {
  year: "numeric",
  month: "2-digit",
})
  .format(now)
  .split("/")
  .reverse()
  .join("-");
const date = ref(formattedDate);
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "SPK NO",
    key: "spkNo",
  },
  {
    title: "MESIN",
    key: "machine",
  },
  {
    title: "KODE",
    key: "code",
  },
  {
    title: "KEADAAN",
    key: "status",
  },
  {
    title: "SHOP",
    key: "shop",
  },

  {
    title: "PEMOHON",
    key: "pemohon",
  },
  {
    title: "MENGAPA & BAGAIMANA",
    key: "ordertitle",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

// data table
const data = ref([]);

async function fetchData() {
  try {
    console.log(date.value);
    const response = await $api(
      "/maintenance-database-system/department-requests",
      {
        params: {
          search: searchQuery.value,
          date: date.value,
        },
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      }
    );

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function openEditPage(id) {
  await router.push({
    path: "/maintenance-database-system/maintenance-report/edit",
    query: { record_id: id },
  });
}

async function openDetailPage(id) {
  await router.push({
    path: "/maintenance-database-system/maintenance-report/detail",
    query: { record_id: id },
  });
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const response = await axios.get(
      "/api/maintenance-database-system/maintenance-report/export",
      {
        responseType: "blob",
        params: {
          search: searchQuery.value,
          date: date.value,
        },
      }
    );

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "maintenance_reports.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
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
          title: 'Maintenance Database System',
          class: 'text-h4',
        },
        {
          title: 'Maintenance Report',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <!-- 👉 products -->
  <VCard class="mb-6">
    <VCardText class="d-flex flex-wrap gap-4">
      <!-- <div class="me-3 d-flex gap-3">
        <AppSelect
          :model-value="itemsPerPage"
          :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
            { value: -1, title: 'All' },
          ]"
          style="inline-size: 6.25rem"
          @update:model-value="itemsPerPage = parseInt($event, 10)"
        />
      </div> -->
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <div style="inline-size: 10rem">
          <AppDateTimePicker
            v-model="date"
            placeholder="Select month"
            :config="{
              dateFormat: 'Y-m',
              mode: 'single',
              enableTime: false,
              enableSeconds: false,
              plugins: [
                new monthSelectPlugin({
                  shorthand: true,
                  dateFormat: 'Y-m',
                  altFormat: 'Y-m',
                }),
              ],
            }"
            append-inner-icon="tabler-calendar"
            @update:modelValue="fetchData()"
          />
        </div>

        <!-- 👉 Search  -->
        <div style="inline-size: 15.625rem">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search"
            v-on:input="fetchData()"
          />
        </div>

        <!-- 👉 Export button -->
        <VBtn
          variant="tonal"
          prepend-icon="tabler-upload"
          @click="handleExport"
          :loading="loadingExport"
        >
          Export
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <!-- 👉 Datatable  -->
    <VDataTable
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      :items="data"
      :headers="headers"
      class="text-no-wrap"
    >
      <template #item.spkNo="{ item }">
        <div class="d-flex align-center">
          {{ item.recordid }}
        </div>
      </template>

      <template #item.machine="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span class="machine-name d-block text-high-emphasis text-truncate">
              {{ item.machinename }}
            </span>
            <small>{{ item.machineno }}</small>
          </div>
        </div>
      </template>

      <template #item.code="{ item }">
        <div class="d-flex align-center">
          {{ item.maintenancecode }}
        </div>
      </template>

      <template #item.status="{ item }">
        <div class="d-flex align-center">
          {{ item.status }}
        </div>
      </template>

      <template #item.shop="{ item }">
        <div class="d-flex align-center">
          {{ item.ordershop }}
        </div>
      </template>

      <template #item.pemohon="{ item }">
        <div class="d-flex align-center">
          {{ item.orderempname }}
        </div>
      </template>

      <template v-slot:header.ordertitle="{ headers }">
        MENGAPA &<br />BAGAIMANA
      </template>

      <template #item.ordertitle="{ item }">
        <div class="multi-line-ellipsis">
          {{ item.ordertitle }}
        </div>
      </template>
      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openDetailPage(item.recordid)">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn @click="openEditPage(item.recordid)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <DetailDepartmentRequestDialog
    v-model:isDialogVisible="isDetailDialogVisible"
    v-model:id="selectedItem"
  />
</template>

<style scoped>
.machine-name {
  font-size: 12px; /* Adjust size */
}
.multi-line-ellipsis {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2; /* Limit to 2 lines */
  line-clamp: 2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: normal; /* Allow text wrapping */
  max-width: 200px; /* Constrain width to trigger ellipsis */
  line-height: 1.2em; /* Adjust the line height */
  height: 2.4em; /* Control the max height (2 lines * line-height) */
}
</style>

<style>
.flatpickr-monthSelect-months {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 10px;
}

.flatpickr-monthSelect-month {
  padding: 10px;
  cursor: pointer;
  text-align: center;
  border-radius: 4px;
}

.flatpickr-monthSelect-month:hover {
  background: #e0e0e0;
}

.flatpickr-monthSelect-month.selected {
  background: #fa0202;
  color: white;
}

.flatpickr-monthSelect-month.flatpickr-disabled {
  color: #999;
  cursor: not-allowed;
  background: #f0f0f0;
}

.flatpickr-monthSelect-month.flatpickr-disabled:hover {
  background: #f0f0f0;
}
</style>
