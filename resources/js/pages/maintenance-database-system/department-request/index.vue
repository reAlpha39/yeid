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
const activeOnly = ref(true);
const selectedMachine = ref(null);
const maintenanceCode = ref(null);
const selectedStaff = ref(null);
const selectedShop = ref(null);

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
    title: "SPK & TGL ORDER",
    key: "recordid",
  },
  {
    title: "APPROVAL",
    key: "approval",
  },
  {
    title: "PEMOHON",
    key: "orderempname",
  },
  {
    title: "JENIS PERBAIKAN",
    key: "maintenancecode",
  },
  {
    title: "MESIN",
    key: "machineno",
  },
  {
    title: "JENIS PEKERJAAN",
    key: "orderjobtype",
  },
  {
    title: "JUMLAH",
    key: "orderqtty",
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

function convertApproval(approval) {
  let result = "";
  (parseInt(approval) & 1) === 1 ? (result += "S") : (result += "B");
  (parseInt(approval) & 2) === 2 ? (result += "S") : (result += "B");
  (parseInt(approval) & 4) === 4 ? (result += "S") : (result += "B");
  return result;
}

async function fetchData() {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests",
      {
        params: {
          search: searchQuery.value,
          date: date.value,
          only_active: activeOnly.value,
          shop_code: selectedShop.value?.shopcode,
          machine_code: selectedMachine.value?.machineno,
          maintenance_code:
            maintenanceCode.value !== null
              ? maintenanceCode.value.split("|")[0]
              : null,
          order_name: selectedStaff.value?.employeename,
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

async function deletePart() {
  try {
    const result = await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(selectedItem.value),
      {
        method: "DELETE",

        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      }
    );

    selectedItem.value = "";
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    toast.error("Failed to delete data");
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

async function openEditPage(id) {
  await router.push({
    path: "/maintenance-database-system/department-request/add",
    query: { record_id: id },
  });
}

async function openDetailPage(id) {
  selectedItem.value = id;
  isDetailDialogVisible.value = true;
}

function openDeleteDialog(partCode) {
  selectedItem.value = partCode;
  isDeleteDialogVisible.value = true;
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get(
      "/api/maintenance-database-system/department-requests/export",
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
        },
      }
    );

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "department_requests.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

function getApprovalIdColor(approval) {
  let approvalId = parseInt(approval);
  if ((approvalId & 4) === 4) {
    return "status-white";
  } else if ((approvalId & 1) === 1 && (approvalId & 2) === 2) {
    return "status-blue";
  } else if ((approvalId & 1) === 1) {
    return "status-light-blue";
  }
}

function getApprovalColor(approval, planid) {
  let approvalId = parseInt(approval);
  let planId = parseInt(planid);
  if (approval >= 112) {
    return "status-indigo";
  } else if (approvalId >= 4) {
    return "status-green";
  } else if (planId > 0 && approvalId < 4) {
    return "status-yellow";
  } else if (planId === 0 && approvalId < 4) {
    return "status-orange";
  }

  return "status-white";
}

const debouncedFetchData = debounce(fetchData, 500);

watch(searchQuery, () => {
  debouncedFetchData();
});

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
          title: 'Department Request',
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
      <VCheckbox
        class="pr-7"
        label="Active saja"
        v-model="activeOnly"
        @update:modelValue="fetchData()"
      />
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

        <!-- 👉 Add button -->
        <VBtn
          v-if="$can('create', 'maintenanceReport')"
          prepend-icon="tabler-plus"
          to="department-request/add"
        >
          Add Department Request
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
        class="text-no-wrap"
      >
        <!-- part name -->
        <template v-slot:header.recordid="{ headers }">
          SPK NO &<br />TGL ORDER
        </template>
        <template #item.recordid="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.recordid }}</span
              >
              <small>{{ item.orderdatetime }}</small>
            </div>
          </div>
        </template>

        <template #item.approval="{ item }">
          <div class="d-flex align-center">
            {{ convertApproval(item.approval) }}
            <div
              class="status-indicator mx-2"
              :class="getApprovalIdColor(item.approval)"
            />
          </div>
        </template>

        <!-- date -->
        <template #item.orderempname="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.orderempname }}</span
              >
              <small>Shop: {{ item.ordershop }}</small>
            </div>
          </div>
        </template>

        <!-- vendor -->
        <template v-slot:header.maintenancecode>
          JENIS<br />PERBAIKAN
        </template>
        <template #item.maintenancecode="{ item }">
          <div class="d-flex align-center">
            {{ item.maintenancecode }}
          </div>
        </template>

        <template #item.machineno="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.machinename }}</span
              >
              <small>{{ item.machineno }}</small>
            </div>
          </div>
        </template>

        <template v-slot:header.orderjobtype> JENIS<br />PEKERJAAN </template>
        <template #item.orderjobtype="{ item }">
          <div class="d-flex align-center">
            {{ item.orderjobtype }}
          </div>
        </template>

        <template #item.orderqtty="{ item }">
          <div class="d-flex align-center">
            {{ item.orderqtty }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <div
              class="status-indicator mx-2"
              :class="getApprovalColor(item.approval, item.planid)"
            />
            <IconBtn @click="openDetailPage(item.recordid)">
              <VIcon icon="tabler-eye" />
            </IconBtn>
            <IconBtn
              v-if="$can('update', 'maintenanceReport')"
              @click="openEditPage(item.recordid)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              v-if="$can('delete', 'maintenanceReport')"
              @click="openDeleteDialog(item.recordid)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </div>
  </VCard>

  <!-- 👉 Delete Dialog  -->
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

        <VBtn color="success" variant="elevated" @click="deletePart()">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>

  <DetailDepartmentRequestDialog
    v-model:isDialogVisible="isDetailDialogVisible"
    v-model:id="selectedItem"
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

.status-yellow {
  background-color: #ffeb3b;
}

.status-orange {
  background-color: #f87d02;
}

.status-light-blue {
  background-color: #c2e9ff;
}

.status-blue {
  background-color: #2d9cdb;
}

.status-indigo {
  background-color: #a59fb2;
}

.status-white {
  background-color: #ffffff;
}
</style>
