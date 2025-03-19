<script setup>
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "masterData",
  },
});

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);

const selectedItem = ref("");
const searchQuery = ref("");

// Data table options for server-side pagination
const loading = ref(false);
const totalItems = ref(0);
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref([{ key: "machineno", order: "asc" }]);
const sortDesc = ref([]);
const appliedOptions = ref({});

// headers
const headers = [
  {
    title: "MACHINE NO",
    key: "machineno",
  },
  {
    title: "MACHINE NAME",
    key: "machinename",
  },
  {
    title: "STATUS",
    key: "status",
  },
  {
    title: "PLANT",
    key: "plantcode",
  },
  {
    title: "SHOP",
    key: "shopcode",
  },
  {
    title: "LINE",
    key: "linecode",
  },
  {
    title: "MODEL",
    key: "modelname",
  },
  {
    title: "MAKER",
    key: "makername",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

// data table
const data = ref([]);

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

    const response = await $api("/master/machines", {
      params: {
        search: searchQuery.value,
        page: options.page || page.value,
        per_page: options.itemsPerPage || itemsPerPage.value,
        ...sortParams,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    // Update data and pagination info
    data.value = response.data;
    totalItems.value = response.pagination.total;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  } finally {
    loading.value = false;
  }
}

function handleOptionsUpdate(options) {
  // Update the sorting values
  sortBy.value = options.sortBy || [];
  sortDesc.value = options.sortDesc || [];

  // Update the pagination values
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;

  appliedOptions.value = options;

  // Fetch the data with new options
  fetchData(options);
}

async function deletePart() {
  try {
    const result = await $api(
      "/master/machines/" + encodeURIComponent(selectedItem.value),
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
    fetchData(appliedOptions.value);
  } catch (err) {
    toast.error("Failed to delete data");
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

async function openEditPage(id) {
  await router.push({
    path: "/master/machine/add",
    query: { machine_no: id },
  });
}

function openDeleteDialog(partCode) {
  selectedItem.value = partCode;
  isDeleteDialogVisible.value = true;
}

function statusType(category) {
  switch (category) {
    case "A":
      return "Active";
    case "D":
      return "Disposed";
    case "R":
      return "Resting";
    case "T":
      return "Transfered";
    default:
      return "";
  }
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/master/machines/export", {
      responseType: "blob",
      params: {
        search: searchQuery.value,
        ...appliedOptions.value,
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
    link.download = "machines.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

const debouncedFetchData = debounce(() => {
  page.value = 1; // Reset to first page when search changes
  fetchData({
    page: 1,
    itemsPerPage: itemsPerPage.value,
    sortBy: sortBy.value,
    sortDesc: sortDesc.value,
  });
}, 500);

watch(searchQuery, () => {
  debouncedFetchData();
});

onMounted(() => {
  fetchData({
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    sortBy: sortBy.value,
    sortDesc: sortDesc.value,
  });
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Master',
          class: 'text-h4',
        },
        {
          title: 'Machine',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <!-- 👉 products -->
  <VCard class="mb-6">
    <VCardText class="d-flex flex-wrap gap-4">
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
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

        <!-- 👉 Add button -->
        <VBtn
          v-if="$can('create', 'masterData')"
          prepend-icon="tabler-plus"
          to="machine/add"
        >
          Add New Machine
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <!-- 👉 Datatable  -->
    <div class="sticky-actions-wrapper">
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="totalItems"
        :loading="loading"
        :headers="headers"
        :items="data"
        :sort-by="sortBy"
        class="text-no-wrap"
        @update:options="handleOptionsUpdate"
        height="562"
      >
        <!-- part name -->
        <template #item.machineno="{ item }">
          <div class="d-flex align-center">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.machineno }}</span
            >
          </div>
        </template>

        <!-- vendor -->
        <template #item.STATUS="{ item }">
          <div class="d-flex align-center">
            {{ statusType(item.status) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="align-center">
            <IconBtn
              v-if="$can('update', 'masterData')"
              @click="openEditPage(item.machineno)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              v-if="$can('delete', 'masterData')"
              @click="openDeleteDialog(item.machineno)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
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
