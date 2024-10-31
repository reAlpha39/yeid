<script setup>
import axios from "axios";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

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
    title: "SPK & TGL ORDER",
    key: "spkNo",
  },
  {
    title: "PEMOHON",
    key: "pemohon",
  },
  {
    title: "JENIS PERBAIKAN",
    key: "jenisPerbaikan",
  },
  {
    title: "MESIN",
    key: "mesin",
  },
  {
    title: "JENIS PEKERJAAN",
    key: "jenisPekerjaan",
  },
  {
    title: "JUMLAH",
    key: "jumlah",
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

async function deletePart() {
  try {
    const result = await $api(
      "/maintenance-database-system/department-requests/" + selectedItem.value,
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
    const response = await axios.get(
      "/api/maintenance-database-system/department-requests/export",
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
    link.download = "department_requests.xlsx";
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
          title: 'Department Request',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <!-- 👉 products -->
  <VCard class="mb-6">
    <VCardText class="d-flex flex-wrap gap-4">
      <div class="me-3 d-flex gap-3">
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
      </div>
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

        <!-- 👉 Add button -->
        <VBtn prepend-icon="tabler-plus" to="department-request/add">
          Add Department Request
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
      <!-- part name -->
      <template v-slot:header.spkNo="{ headers }">
        SPK NO &<br />TGL ORDER
      </template>
      <template #item.spkNo="{ item }">
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

      <!-- date -->
      <template #item.pemohon="{ item }">
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
      <template v-slot:header.jenisPerbaikan="{ headers }">
        JENIS<br />PERBAIKAN
      </template>
      <template #item.jenisPerbaikan="{ item }">
        <div class="d-flex align-center">
          {{ item.maintenancecode }}
        </div>
      </template>

      <template #item.mesin="{ item }">
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

      <template v-slot:header.jenisPekerjaan="{ headers }">
        JENIS<br />PEKERJAAN
      </template>
      <template #item.jenisPekerjaan="{ item }">
        <div class="d-flex align-center">
          {{ item.orderjobtype }}
        </div>
      </template>

      <template #item.jumlah="{ item }">
        <div class="d-flex align-center">
          {{ item.orderqtty }}
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
          <IconBtn @click="openDeleteDialog(item.recordid)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
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
