<script setup>
import axios from "axios";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import moment from "moment";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "pressShotProdData",
  },
});

const toast = useToast();

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);
const searchQuery = ref("");
const selectedMachineNo = ref(null);
const selectedModelDie = ref(null);
const isDetailDialogVisible = ref(false);

const selectedItem = ref("");

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

const data = ref([]);
const modelDieData = ref([]);
const machineNoData = ref([]);

async function fetchData() {
  try {
    let targetDateSplit = date.value.split("-");
    const response = await $api("/press-shot/productions", {
      params: {
        search: searchQuery.value,
        target_date: targetDateSplit[0] + targetDateSplit[1],
        model: selectedModelDie.value?.model,
        die_no: selectedModelDie.value?.dieno,
        machine_no: selectedMachineNo.value?.machineno,
      },
    });

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    let targetDateSplit = date.value.split("-");

    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/press-shot/productions/export", {
      responseType: "blob",
      params: {
        search: searchQuery.value,
        target_date: targetDateSplit[0] + targetDateSplit[1],
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
    link.download = "production-data.xlsx";
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

function openDetailPage(data) {
  selectedItem.value = data;
  isDetailDialogVisible.value = true;
}

const headers = [
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
    title: "START DATE",
    key: "startdatetime",
  },
  {
    title: "END DATE",
    key: "enddatetime",
  },
  {
    title: "SHOT COUNT",
    key: "shotcount",
  },
  {
    title: "REASON",
    key: "reason",
  },
  {
    title: "EMPLOYEE",
    key: "employeecode",
  },
  {
    title: "UPDATE TIME",
    key: "updatetime",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

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
          title: 'Production Data',
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

      <!-- <div style="inline-size: 15.625rem">
        <AppTextField
          v-model="searchQuery"
          placeholder="Search"
          v-on:input="fetchData()"
        />
      </div> -->

      <VSpacer />
      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <AppDateTimePicker
          style="inline-size: 10rem"
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

        <VBtn
          v-if="$can('create', 'pressShotProdData')"
          prepend-icon="tabler-edit"
          to="production-data/add"
        >
          Create Production Data
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <div class="sticky-actions-wrapper">
      <VDataTable
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="data"
        :headers="headers"
        :sort-by="[{ key: 'startdatetime', order: 'desc' }]"
        class="text-no-wrap"
        height="562"
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

        <template #item.startdatetime="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ formatDateTime(item.startdatetime).formattedDate }}</span
              >
              <small>{{
                formatDateTime(item.startdatetime).formattedTime
              }}</small>
            </div>
          </div>
        </template>

        <template #item.enddatetime="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ formatDateTime(item.enddatetime).formattedDate }}</span
              >
              <small>{{
                formatDateTime(item.enddatetime).formattedTime
              }}</small>
            </div>
          </div>
        </template>

        <template v-slot:header.dieunitno> DIE<br />UNIT NO# </template>

        <template v-slot:header.shotcount> SHOT<br />COUNT </template>

        <template #item.shotcount="{ item }">
          {{ Intl.NumberFormat().format(item.shotcount) }}
        </template>

        <template #item.updatetime="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ formatDateTime(item.updatetime).formattedDate }}</span
              >
              <small>{{ formatDateTime(item.updatetime).formattedTime }}</small>
            </div>
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <IconBtn @click="openDetailPage(item)">
              <VIcon icon="tabler-eye" />
            </IconBtn>
            <!-- <IconBtn @click="openDeleteDialog(item.makercode)">
            <VIcon icon="tabler-trash" />
          </IconBtn> -->
          </div>
        </template>
      </VDataTable>
    </div>
  </VCard>

  <DetailProductionDataDialog
    v-model:isDialogVisible="isDetailDialogVisible"
    v-model:data="selectedItem"
  />
</template>
