<script setup>
import axios from "axios";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "mtDbsMtReport",
  },
});

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const isDetailDialogVisible = ref(false);

const selectedItem = ref("");
const searchQuery = ref("");
const selectedMachine = ref(null);
const maintenanceCode = ref(null);
const selectedStaff = ref(null);
const selectedShop = ref(null);
const selectedStatus = ref(null);

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
    key: "recordid",
  },
  {
    title: "STATUS",
    key: "approval_status",
  },
  {
    title: "MESIN",
    key: "machineno",
  },
  {
    title: "KODE",
    key: "maintenancecode",
  },
  {
    title: "SHOP",
    key: "ordershop",
  },
  {
    title: "PEMOHON",
    key: "orderempname",
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
const machineNoData = ref([]);
const staffs = ref([]);
const shops = ref([]);
const maintenanceCodes = [
  "01|UM",
  "02|BM",
  "03|TBC",
  "04|TBA",
  "05|PvM",
  "06|FM",
  "07|CM",
  "08|CHECH",
  "09|LAYOUT",
];

const status = [
  "PENDING",
  "PARTIALLY APPROVED",
  "APPROVED",
  "REVISED",
  "REVISION",
  "REJECTED",
  "FINISH",
];

function convertApprovalStatus(status) {
  const statusMap = {
    approved: "Approved",
    partially_approved: "Partially Approved",
    rejected: "Rejected",
    revision: "Need Revise",
    revised: "Revised",
    finish: "Finish",
    draft: "Draft",
    pending: "Pending",
  };
  return statusMap[status] || "-";
}

async function fetchData() {
  try {
    // console.log(date.value);
    const response = await $api(
      "/maintenance-database-system/department-requests",
      {
        params: {
          search: searchQuery.value,
          date: date.value,
          shop_code: selectedShop.value?.shopcode,
          machine_code: selectedMachine.value?.machineno,
          maintenance_code:
            maintenanceCode.value !== null
              ? maintenanceCode.value.split("|")[0]
              : null,
          order_name: selectedStaff.value?.employeename,
          status: selectedStatus.value,
          approved_only: true,
        },
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      }
    );

    data.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataMachine() {
  try {
    const response = await $api("/master/machines");

    machineNoData.value = response.data;

    machineNoData.value.forEach((data) => {
      data.title = data.machineno + " | " + data.machinename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataEmployee() {
  try {
    const response = await $api("/master/employees");

    staffs.value = response.data;
    staffs.value.forEach((data) => {
      data.title = data.employeename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops");

    shops.value = response.data;

    shops.value.forEach((data) => {
      data.title = data.shopcode + " | " + data.shopname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function openEditPage(id) {
  const route = {
    path: "/maintenance-database-system/maintenance-report/edit",
    query: { record_id: id },
  };

  const url = router.resolve(route).href;
  window.open(url, "_blank");
}

function openDetailPage(id) {
  const route = {
    path: "/maintenance-database-system/maintenance-report/detail",
    query: { record_id: id },
  };

  const url = router.resolve(route).href;
  window.open(url, "_blank");
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get(
      "/api/maintenance-database-system/maintenance-report/export",
      {
        responseType: "blob",
        headers: accessToken
          ? {
              Authorization: `Bearer ${accessToken}`,
            }
          : {},
        params: {
          search: searchQuery.value,
          date: date.value,
          shop_code: selectedShop.value?.shopcode,
          machine_code: selectedMachine.value?.machineno,
          maintenance_code:
            maintenanceCode.value !== null
              ? maintenanceCode.value.split("|")[0]
              : null,
          order_name: selectedStaff.value?.employeename,
          status: selectedStatus.value,
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

function getApprovalColor(approval) {
  if (approval === "pending" || approval === "draft") {
    return "status-pending";
  } else if (approval === "partially_approved") {
    return "status-partially-approved";
  } else if (approval === "revision" || approval === "revised") {
    return "status-revised";
  } else if (approval === "approved") {
    return "status-approved";
  } else if (approval === "finish") {
    return "status-finish";
  } else if (approval === "rejected") {
    return "status-rejected";
  }

  return "status-white";
}

const debouncedFetchData = debounce(fetchData, 500);

watch(
  [
    searchQuery,
    date,
    selectedShop,
    selectedMachine,
    maintenanceCode,
    selectedStaff,
    selectedStatus,
  ],
  () => {
    debouncedFetchData();
  }
);

onMounted(() => {
  fetchData();
  fetchDataMachine();
  fetchDataEmployee();
  fetchDataShop();
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
      <div style="inline-size: 15.625rem">
        <AppTextField v-model="searchQuery" placeholder="Search" />
      </div>

      <div style="inline-size: 10rem">
        <AppAutocomplete
          v-model="selectedStatus"
          placeholder="Select status"
          :items="status"
          return-object
          clearable
          clear-icon="tabler-x"
          outlined
        />
      </div>

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

      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
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

    <VDivider class="mt-2" />

    <VCardText class="d-flex flex-wrap gap-4">
      <VRow>
        <VCol>
          <AppAutocomplete
            v-model="selectedShop"
            placeholder="Select shop"
            item-title="title"
            :items="shops"
            return-object
            clearable
            clear-icon="tabler-x"
            outlined
            @update:modelValue="fetchData()"
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="selectedMachine"
            placeholder="Select machine no"
            :items="machineNoData"
            item-title="title"
            return-object
            clearable
            clear-icon="tabler-x"
            outlined
            @update:modelValue="fetchData()"
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="maintenanceCode"
            placeholder="Select perbaikan"
            :items="maintenanceCodes"
            clearable
            clear-icon="tabler-x"
            outlined
            @update:modelValue="fetchData()"
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="selectedStaff"
            placeholder="Select staff"
            item-title="title"
            :items="staffs"
            return-object
            clearable
            clear-icon="tabler-x"
            outlined
            @update:modelValue="fetchData()"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDivider class="mt-4" />

    <!-- 👉 Datatable  -->
    <div class="sticky-actions-wrapper">
      <VDataTable
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="data"
        :headers="headers"
        fixed-header
        :sort-by="[{ key: 'recordid', order: 'desc' }]"
        class="text-no-wrap"
        height="562"
      >
        <template #item.approval_status="{ item }">
          {{ convertApprovalStatus(item?.approval_record?.approval_status) }}
        </template>

        <template #item.machineno="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="machine-name d-block text-high-emphasis text-truncate"
              >
                {{ item.machinename }}
              </span>
              <small>{{ item.machineno }}</small>
            </div>
          </div>
        </template>

        <template v-slot:header> MENGAPA &<br />BAGAIMANA </template>

        <template #item.ordertitle="{ item }">
          <div class="multi-line-ellipsis">
            {{ item.ordertitle }}
          </div>
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-right gap-0">
            <div
              v-if="item?.approval_record?.approval_status !== undefined"
              class="status-indicator ml-2 mr-4"
              :class="getApprovalColor(item?.approval_record?.approval_status)"
            />
            <IconBtn @click="openDetailPage(item.recordid)">
              <VIcon icon="tabler-eye" />
            </IconBtn>
            <IconBtn
              v-if="$can('update', 'mtDbsMtReport') && item.can_update_report"
              @click="openEditPage(item.recordid)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </div>
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
.status-indicator {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  margin: auto;
}

.status-finish {
  background-color: #28c76f;
}

.status-revised {
  background-color: #e87768;
}

.status-pending {
  background-color: #f87d02;
}

.status-partially-approved {
  background-color: #c2e9ff;
}

.status-approved {
  background-color: #2d9cdb;
}

.status-rejected {
  background-color: #fa0202;
}
</style>
