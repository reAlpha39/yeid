<script setup>
import axios from "axios";
import moment from "moment";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "pressShotPartList",
  },
});

const toast = useToast();
const router = useRouter();

const selectedMachineNo = ref(null);
const selectedModelDie = ref(null);
const isDetailDialogVisible = ref(false);

const selectedItem = ref("");
const selectedStatus = ref(null);

// Data table options
const loading = ref(false);
const totalItems = ref(0);
const itemsPerPage = ref(10);
const page = ref(1);
const data = ref([]);
const searchQuery = ref("");
const sortBy = ref([{ key: "partcode", order: "asc" }]);
const sortDesc = ref([]);
const options = ref({});

const date = ref(moment().format("YYYY-MM"));

const modelDieData = ref([]);
const machineNoData = ref([]);
const statusData = ["RED", "BLUE", "YELLOW"];

async function fetchData(options = {}) {
  loading.value = true;
  try {
    // Format sort parameters
    const sortParams = {};
    if (options.sortBy?.[0]) {
      // Check if sortBy is an object
      const sortColumn =
        typeof options.sortBy[0] === "object"
          ? options.sortBy[0].key
          : options.sortBy[0];

      sortParams.sortBy = sortColumn;
      sortParams.sortDirection = options.sortDesc?.[0] ? "desc" : "asc";
    }

    let targetDateSplit = date.value.split("-");
    const response = await $api("/press-shot/parts", {
      params: {
        part_code: searchQuery.value,
        status: selectedStatus.value,
        // year: targetDateSplit[0] + targetDateSplit[1],
        model: selectedModelDie.value?.model,
        die_no: selectedModelDie.value?.dieno,
        machine_no: selectedMachineNo.value?.machineno,
        page: page.value,
        per_page: itemsPerPage.value,
        ...sortParams,
        ...options,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    // Update data and pagination info
    data.value = response.data;
    totalItems.value = response.pagination.total;
  } catch (err) {
    console.log(err);
  } finally {
    loading.value = false;
  }
}

function handleOptionsUpdate(data) {
  // Update the sorting values
  sortBy.value = data.sortBy || [];
  sortDesc.value = data.sortDesc || [];

  // Update the pagination values
  page.value = data.page;
  itemsPerPage.value = data.itemsPerPage;

  options.value = data;

  // Fetch the data with new options
  fetchData(data);
}

async function refreshTable() {
  await fetchData(options.value);
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/press-shot/parts/export", {
      responseType: "blob",
      params: {
        part_code: searchQuery.value,
        status: selectedStatus.value,
        // year: targetDateSplit[0] + targetDateSplit[1],
        model: selectedModelDie.value?.model,
        die_no: selectedModelDie.value?.dieno,
        machine_no: selectedMachineNo.value?.machineno,
      },
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "parts.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

const loadingDownload = ref(false);

async function handleLogDownload() {
  loadingDownload.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/download-today-log", {
      responseType: "blob",
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;

    const today = new Date();
    const filename =
      today.getFullYear() +
      String(today.getMonth() + 1).padStart(2, "0") +
      String(today.getDate()).padStart(2, "0") +
      ".log";

    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Log download failed:", error);
  } finally {
    loadingDownload.value = false;
  }
}

async function fetchDataModelDie() {
  try {
    const response = await $api("/press-shot/exchange/model-dies", {
      params: {
        machine_no: "",
      },
    });

    modelDieData.value = response.data;

    modelDieData.value.forEach((data) => {
      data.title = data.model + " | " + data.dieno;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataMachineNo() {
  try {
    const response = await $api("/press-shot/exchange/machines-no");

    machineNoData.value = response.data;

    machineNoData.value.forEach((data) => {
      data.title = data.machineno + " | " + data.machinename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function formatDateTime(dateString) {
  let momentDate;

  // Check if the date is in numeric format (20241105094958)
  if (/^\d{14}$/.test(dateString)) {
    momentDate = moment(dateString, "YYYYMMDDHHmmss");
  }
  // Check if the date is in YYYY-MM-DD HH:mm:ss format
  else if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateString)) {
    momentDate = moment(dateString, "YYYY-MM-DD HH:mm:ss");
  } else {
    return "Invalid date format";
  }

  moment.locale("id");

  const formattedDate = momentDate.format("D MMMM YYYY");
  const formattedTime = momentDate.format("HH:mm:ss");

  return { formattedDate, formattedTime };
}

function openDetailPage(id) {
  selectedItem.value = id;
  console.log("aaaaaaaa " + selectedItem.value);
  isDetailDialogVisible.value = true;
}

async function openEditPage(machineno, model, dieno, processname, partcode) {
  const route = {
    path: "/press-shot/part-list/exchange-part",
    query: {
      machineno: machineno,
      model: model,
      dieno: dieno,
      processname: processname,
      partcode: partcode,
    },
  };

  const url = router.resolve(route).href;
  window.open(url, "_blank");
}

function getStatusDescription(item) {
  if (parseInt(item?.counter ?? 0) > parseInt(item?.companylimit ?? 0)) {
    return "Counter > Max Limit";
  }

  if (parseInt(item?.counter ?? 0) > parseInt(item?.makerlimit ?? 0)) {
    return "Counter > Fix Limit";
  }

  if (parseInt(item?.minstock ?? 0) > parseInt(item?.currentstock ?? 0)) {
    return "Min Stock > Actual Stock";
  }
  return "Normal";
}

function getStatusColor(item) {
  if (parseInt(item?.counter ?? 0) > parseInt(item?.companylimit ?? 0)) {
    return "status-red";
  }

  if (parseInt(item?.counter ?? 0) > parseInt(item?.makerlimit ?? 0)) {
    return "status-blue";
  }

  if (parseInt(item?.minstock ?? 0) > parseInt(item?.currentstock ?? 0)) {
    return "status-yellow";
  }
  return "status-green";

  // // COUNTER -- FIX LIMIT
  // if (
  //   parseInt(item?.counter ?? 0) > 0 &&
  //   parseInt(item?.counter ?? 0) > parseInt(item?.makerlimit ?? 0)
  // ) {
  //   return "status-red";
  //   // COUNTER -- TEMP LIMIT
  // } else if (
  //   parseInt(item?.counter ?? 0) > 0 &&
  //   parseInt(item?.counter ?? 0) > parseInt(item?.companylimit ?? 0)
  // ) {
  //   return "status-yellow";
  //   // MIN STOCK -- ACTUAL STOCK
  // } else if (
  //   parseInt(item?.minstock ?? 0) > 0 &&
  //   parseInt(item?.minstock ?? 0) > parseInt(item?.currentstock ?? 0)
  // ) {
  //   return "status-red";
  // }
  // return "status-green";
}

// headers
const headers = [
  {
    title: "MACH SPEC",
    key: "machinename",
  },
  {
    title: "MACHINE NO",
    key: "machineno",
  },
  {
    title: "MODEL",
    key: "model",
  },
  {
    title: "DIE#",
    key: "dieno",
  },
  {
    title: "DIE UNIT NO#",
    key: "dieunitno",
  },
  {
    title: "PROCESS",
    key: "processname",
  },
  {
    title: "PART",
    key: "partcode",
  },
  {
    title: "CATEGORY",
    key: "category",
  },
  {
    title: "COUNTER",
    key: "counter",
  },
  {
    title: "MAX LIMIT",
    key: "companylimit",
  },
  {
    title: "FIX LIMIT",
    key: "makerlimit",
  },
  {
    title: "QTY/DIE",
    key: "qttyperdie",
  },
  {
    title: "DRAWING#",
    key: "drawingno",
  },
  {
    title: "SPECIFICATION",
    key: "note",
  },
  {
    title: "LAST EXCHANGE DATE",
    key: "exchangedatetime",
  },
  {
    title: "MIN STOCK",
    key: "minstock",
  },
  {
    title: "ACTUAL STOCK",
    key: "currentstock",
  },
  {
    title: "UNIT PRICE",
    key: "unitprice",
  },
  {
    title: "BRAND",
    key: "brand",
  },
  {
    title: "SUPPLIER",
    key: "vendorname",
  },
  {
    title: "IMPORT/LOCAL",
    key: "origin",
  },
  {
    title: "LOCATION",
    key: "address",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
    align: "center fixed",
    class: "fixed",
  },
];

const debouncedFetchData = debounce(fetchData, 500);

watch(searchQuery, () => {
  debouncedFetchData();
});

onMounted(() => {
  fetchData();
  fetchDataModelDie();
  fetchDataMachineNo();
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Press Shot',
          class: 'text-h4',
        },
        {
          title: 'Part List',
          class: 'text-h4',
        },
      ]"
    />
  </div>

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

      <div style="inline-size: 15.625rem">
        <AppTextField v-model="searchQuery" placeholder="Search part" />
      </div>

      <VSpacer />
    </VCardText>
    <VDivider class="mt-4" />
    <VCardText class="d-flex flex-wrap gap-4">
      <AppAutocomplete
        v-model="selectedMachineNo"
        placeholder="Select machine no"
        :items="machineNoData"
        item-title="title"
        return-object
        clearable
        clear-icon="tabler-x"
        outlined
        @update:modelValue="fetchData()"
      />

      <AppAutocomplete
        v-model="selectedModelDie"
        placeholder="Select model die"
        :items="modelDieData"
        item-title="title"
        return-object
        clearable
        clear-icon="tabler-x"
        outlined
        @update:modelValue="fetchData()"
      />

      <AppAutocomplete
        v-model="selectedStatus"
        placeholder="Select status"
        :items="statusData"
        return-object
        clearable
        clear-icon="tabler-x"
        outlined
        @update:modelValue="fetchData()"
      />

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
          variant="tonal"
          prepend-icon="tabler-list"
          @click="handleLogDownload"
          :loading="loadingDownload"
        >
          Log
        </VBtn>

        <VBtn prepend-icon="tabler-refresh" @click="refreshTable">
          Refresh
        </VBtn>

        <!-- <VBtn prepend-icon="tabler-edit" to="part-list/exchange-part">
          Exchange Part
        </VBtn> -->
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <div class="sticky-actions-wrapper">
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="totalItems"
        :loading="loading"
        :headers="headers"
        :items="data"
        :sort-by="sortBy"
        :sort-desc="sortDesc"
        class="text-no-wrap"
        height="562"
        @update:options="handleOptionsUpdate"
      >
        <template #item.employeecode="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.employeename ?? "-" }}</span
              >
              <small>{{ item.employeecode ?? "-" }}</small>
            </div>
          </div>
        </template>

        <template #item.partcode="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.partname ?? "-" }}</span
              >
              <small>{{ item.partcode ?? "-" }}</small>
            </div>
          </div>
        </template>

        <template v-slot:header.dieunitno> DIE<br />UNIT NO# </template>

        <template v-slot:header.serialno> SERIAL<br />NO </template>

        <template #item.counter="{ item }">
          {{ formatNumber(item.counter) }}
        </template>

        <template #item.companylimit="{ item }">
          {{ formatNumber(item.companylimit) }}
        </template>

        <template #item.makerlimit="{ item }">
          {{ formatNumber(item.makerlimit) }}
        </template>

        <template v-slot:header.exchangedatetime>
          LAST EXCHANGE<br />DATE
        </template>

        <template #item.exchangedatetime="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ formatDateTime(item.exchangedatetime).formattedDate }}</span
              >
              <small>{{
                formatDateTime(item.exchangedatetime).formattedTime
              }}</small>
            </div>
          </div>
        </template>

        <template v-slot:header.minstock> MIN<br />STOCK </template>

        <template #item.minstock="{ item }">
          {{ formatNumber(item.minstock) }}
        </template>

        <template v-slot:header.currentstock> ACTUAL<br />STOCK </template>

        <template #item.currentstock="{ item }">
          {{ formatNumber(item.currentstock) }}
        </template>

        <template #item.unitprice="{ item }">
          {{ formatCurrency(item.currency, item.unitprice) }}
        </template>

        <template v-slot:header.origin> IMPORT/<br />LOCAL </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <div class="status-indicator mx-2" :class="getStatusColor(item)">
              <v-tooltip activator="parent" location="top">
                {{ getStatusDescription(item) }}</v-tooltip
              >
            </div>
            <!-- <IconBtn @click="openDetailPage(item.exchangedatetime)">
            <VIcon icon="tabler-eye" />
          </IconBtn> -->
            <IconBtn
              v-if="$can('update', 'pressShotPartList')"
              @click="
                openEditPage(
                  item.machineno,
                  item.model,
                  item.dieno,
                  item.processname,
                  item.partcode
                )
              "
            >
              <VIcon icon="tabler-exchange" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </div>
    <template #bottom>
      <TablePagination
        v-model:page="page"
        :items-per-page="itemsPerPage"
        :total-items="totalItems"
      />
    </template>
  </VCard>

  <DetailExchangeDataDialog
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

.status-red {
  background-color: #ff4444;
}

.status-yellow {
  background-color: #ffeb3b;
}

.status-green {
  background-color: #4caf50;
}

.status-blue {
  background-color: #2d9cdb;
}
</style>
