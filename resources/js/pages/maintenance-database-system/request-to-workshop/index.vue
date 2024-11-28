<script setup>
import axios from "axios";
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

const selectedItem = ref();
const searchQuery = ref("");
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

const now = new Date();
const currentYear = now.getFullYear();
const years = ref([]);
const year = ref(currentYear);

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}
// data table
const data = ref([]);

async function fetchData() {
  try {
    const response = await $api(
      "/maintenance-database-system/request-workshop",
      {
        params: {
          search: searchQuery.value,
          year: year.value.toString(),
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
      "/maintenance-database-system/request-workshop/" +
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

function convertAsapFlagId(id) {
  switch (id) {
    case "1":
      return "JIG";
    case "2":
      return "W/S";
    case "3":
      return "FAC";
    default:
      return "";
  }
}

// headers
const headers = [
  {
    title: "REQUEST",
    key: "request",
  },
  {
    title: "TITLE",
    key: "title",
  },
  {
    title: "PEMOHON",
    key: "ordername",
  },
  {
    title: "REQ FINISH DATE",
    key: "reqfinishdate",
  },

  {
    title: "CATEGORY",
    key: "category",
  },
  {
    title: "STATUS",
    key: "status",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

async function openEditPage(id) {
  await router.push({
    path: "/maintenance-database-system/request-to-workshop/add",
    query: { wsrid: id },
  });
}

async function openDetailPage(id) {
  await router.push({
    path: "/maintenance-database-system/request-to-workshop/detail",
    query: { wsrid: id },
  });
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
      "/api/maintenance-database-system/request-workshop/export",
      {
        responseType: "blob",
        headers: accessToken
          ? {
              Authorization: `Bearer ${accessToken}`,
            }
          : {},
        params: {
          search: searchQuery.value,
          year: year.value.toString(),
        },
      }
    );

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "request_to_workshops.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

const debouncedFetchData = debounce(fetchData, 500);

watch(searchQuery, () => {
  debouncedFetchData();
});

onMounted(() => {
  getLastTenYears();
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
          title: 'Request to Workshop',
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
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <AppAutocomplete
          v-model="year"
          :items="years"
          outlined
          @update:model-value="fetchData()"
        />
        <!-- 👉 Search  -->
        <div style="inline-size: 15.625rem">
          <AppTextField v-model="searchQuery" placeholder="Search" />
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

        <VBtn
          v-if="$can('create', 'maintenanceReport')"
          prepend-icon="tabler-plus"
          to="request-to-workshop/add"
        >
          Add Request
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
        class="text-no-wrap"
      >
        <template #item.request="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span class="d-block text-high-emphasis text-truncate">
                {{ item.wsrid }}
              </span>
              <small>{{ item.requestdate }}</small>
            </div>
          </div>
        </template>

        <template #item.title="{ item }">
          <div class="limited-title">
            {{ item.title }}
          </div>
        </template>

        <template #item.ordername="{ item }">
          <div class="d-flex align-center">
            {{ item.ordername }}
          </div>
        </template>

        <template v-slot:header.reqfinishdate="{ headers }">
          REQ.<br />FINISH DATE
        </template>

        <template #item.reqfinishdate="{ item }">
          <div class="d-flex align-center">
            {{ item.reqfinishdate }}
          </div>
        </template>

        <template #item.category="{ item }">
          <div class="d-flex align-center">
            {{ convertAsapFlagId(item.asapflag) }}
          </div>
        </template>

        <template #item.status="{ item }">
          <div class="d-flex align-center">
            {{ item.status }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <IconBtn @click="openDetailPage(item.wsrid)">
              <VIcon icon="tabler-eye" />
            </IconBtn>
            <IconBtn
              v-if="$can('update', 'maintenanceReport')"
              @click="openEditPage(item.wsrid)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              v-if="$can('delete', 'maintenanceReport')"
              @click="openDeleteDialog(item.wsrid)"
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
</template>

<style scoped>
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
.limited-title {
  max-width: 200px; /* Limit the width to 200px (adjust as needed) */
  overflow: hidden; /* Hide anything beyond the set width */
  white-space: nowrap; /* Prevent the text from wrapping to the next line */
  text-overflow: ellipsis; /* Add the ellipsis (...) when the text overflows */
}
</style>
