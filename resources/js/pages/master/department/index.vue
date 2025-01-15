<script setup>
import AddDepartmentDrawer from "@/components/drawers/AddDepartmentDrawer.vue";
import axios from "axios";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "masterData",
  },
});

const toast = useToast();

const isDeleteDialogVisible = ref(false);
const isDrawerOpen = ref(false);

const selectedId = ref("");
const searchQuery = ref("");
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "DEPARTMENT CODE",
    key: "code",
  },
  {
    title: "DEPARTMENT NAME",
    key: "name",
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
    const response = await $api("/master/departments", {
      params: {
        search: searchQuery.value,
      },
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deleteItem() {
  try {
    const result = await $api(
      "/master/departments/" + encodeURIComponent(selectedId.value),
      {
        method: "DELETE",

        onResponseError({ response }) {
          // errors.value = response._data.errors;
        },
      }
    );

    selectedId.value = "";
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    toast.error("Failed to delete data");
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

function openDeleteDialog(partCode) {
  selectedId.value = partCode;
  isDeleteDialogVisible.value = true;
}

async function openEditPartPage(partCode) {
  selectedId.value = partCode;
  isDrawerOpen.value = true;
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/master/departments/export", {
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
    link.download = "departments.xlsx";
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

watch(
  () => isDrawerOpen.value,
  (newVal) => {
    if (!newVal) {
      // When the drawer is closed
      selectedId.value = undefined;
    }
  }
);

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
          title: 'Master',
          class: 'text-h4',
        },
        {
          title: 'Department',
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
          @click="isDrawerOpen = !isDrawerOpen"
        >
          Add New Department
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
      :sort-by="[{ key: 'code', order: 'asc' }]"
      class="text-no-wrap"
      height="562"
    >
      <!-- part name -->
      <template #item.code="{ item }">
        <div class="d-flex align-center">
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            >{{ item.code }}</span
          >
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn
            v-if="$can('update', 'masterData')"
            @click="openEditPartPage(item.id)"
          >
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'masterData')"
            @click="openDeleteDialog(item.id)"
          >
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

        <VBtn color="success" variant="elevated" @click="deleteItem()">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Add Item Drawer -->
  <AddDepartmentDrawer
    v-model:isDrawerOpen="isDrawerOpen"
    v-model:id="selectedId"
    @submit="fetchData"
  />
</template>
