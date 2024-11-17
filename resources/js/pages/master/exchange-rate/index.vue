<script setup>
import AddExchangeRateDrawer from "@/components/drawers/AddExchangeRateDrawer.vue";
import axios from "axios";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "machine",
  },
});

const toast = useToast();

const isDeleteDialogVisible = ref(false);
const isDrawerOpen = ref(false);

const selectedId = ref("");
const searchQuery = ref();
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "YEAR",
    key: "year",
  },
  {
    title: "USD2IDR",
    key: "usd2idr",
  },
  {
    title: "JPY2IDR",
    key: "jpy2idr",
  },
  {
    title: "EUR2IDR",
    key: "eur2idr",
  },
  {
    title: "SGD2IDR",
    key: "sgd2idr",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

let idr = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
});

// data table
const data = ref([]);

async function fetchData() {
  try {
    const response = await $api("/master/systems", {
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
    const result = await $api("/master/systems/" + selectedId.value, {
      method: "DELETE",

      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

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
    const response = await axios.get("/api/master/systems/export", {
      responseType: "blob",
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "currency_rates.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

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
          title: 'Exchange Rate',
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
        <VBtn
          v-if="$can('create', 'machine')"
          prepend-icon="tabler-plus"
          @click="isDrawerOpen = !isDrawerOpen"
        >
          Add New Exchange Rate
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
      <template #item.year="{ item }">
        <div class="d-flex align-center">
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            >{{ item.year }}</span
          >
        </div>
      </template>

      <!-- date -->
      <template #item.usd2idr="{ item }">
        <div class="d-flex align-center">
          {{ idr.format(parseFloat(item.usd2idr)) }}
        </div>
      </template>

      <!-- vendor -->
      <template #item.jpy2idr="{ item }">
        <div class="d-flex align-center">
          {{ idr.format(parseFloat(item.jpy2idr)) }}
        </div>
      </template>

      <template #item.eur2idr="{ item }">
        <div class="d-flex align-center">
          {{ idr.format(parseFloat(item.eur2idr)) }}
        </div>
      </template>

      <template #item.sgd2idr="{ item }">
        <div class="d-flex align-center">
          {{ idr.format(parseFloat(item.sgd2idr)) }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn
            v-if="$can('update', 'machine')"
            @click="openEditPartPage(item.year)"
          >
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'machine')"
            @click="openDeleteDialog(item.year)"
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
  <AddExchangeRateDrawer
    v-model:isDrawerOpen="isDrawerOpen"
    v-model:id="selectedId"
    @submit="fetchData"
  />
</template>
