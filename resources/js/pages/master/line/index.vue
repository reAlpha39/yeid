<script setup>
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

const selectedLineCode = ref("");
const selectedShopCode = ref("");
const searchQuery = ref("");
const shopQuery = ref();
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "LINE CODE",
    key: "linecode",
  },
  {
    title: "LINE NAME",
    key: "linename",
  },
  {
    title: "SHOP",
    key: "shopcode",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

// data table
const data = ref([]);
const shops = ref([]);

async function fetchData() {
  try {
    const response = await $api("/master/lines", {
      params: {
        query: searchQuery.value,
        shop_code: shopQuery.value,
      },
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    data.value = response.data;
    console.log(data.value);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deleteItem() {
  try {
    const result = await $api(
      "/master/lines/" + selectedShopCode.value + "/" + selectedLineCode.value,
      {
        method: "DELETE",

        onResponseError({ response }) {
          // errors.value = response._data.errors;
        },
      }
    );

    selectedLineCode.value = undefined;
    selectedShopCode.value = undefined;
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    toast.error("Failed to delete data");
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops", {
      onResponseError({ response }) {
        // toast.error(response._data.error);
      },
    });

    shops.value = response.data;
  } catch (err) {
    // toast.error("Failed to fetch data");
    console.log(err);
  }
}

function openDeleteDialog(item) {
  selectedLineCode.value = item.linecode;
  selectedShopCode.value = item.shopcode;
  isDeleteDialogVisible.value = true;
}

async function openEditPartPage(item) {
  selectedLineCode.value = item.linecode;
  selectedShopCode.value = item.shopcode;
  isDrawerOpen.value = true;
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const response = await axios.get("/api/master/lines/export", {
      responseType: "blob",
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "lines.xlsx";
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
      selectedLineCode.value = undefined;
      selectedShopCode.value = undefined;
    }
  }
);

onMounted(() => {
  fetchDataShop();
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
          title: 'Line',
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
      <AppAutocomplete
        v-model="shopQuery"
        placeholder="Select Shop"
        item-title="shopcode"
        :items="shops"
        outlined
        clearable
        maxlength="4"
        @update:model-value="fetchData()"
      />
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
          v-if="$can('create', 'masterData')"
          prepend-icon="tabler-plus"
          @click="isDrawerOpen = !isDrawerOpen"
        >
          Add New Line
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
      <template #item.linecode="{ item }">
        <div class="d-flex align-center">
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            >{{ item.linecode }}</span
          >
        </div>
      </template>

      <!-- date -->
      <template #item.linename="{ item }">
        <div class="d-flex align-center">
          {{ item.linename }}
        </div>
      </template>

      <!-- vendor -->
      <template #item.shopcode="{ item }">
        <div class="d-flex align-center">
          {{ item.shopcode }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn
            v-if="$can('update', 'masterData')"
            @click="openEditPartPage(item)"
          >
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'masterData')"
            @click="openDeleteDialog(item)"
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
  <AddLineDrawer
    v-model:isDrawerOpen="isDrawerOpen"
    v-model:lineCode="selectedLineCode"
    v-model:shopCode="selectedShopCode"
    @submit="fetchData"
  />
</template>
