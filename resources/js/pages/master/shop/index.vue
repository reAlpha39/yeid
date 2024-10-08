<script setup>
import AddShopDrawer from "@/components/drawers/AddShopDrawer.vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const isAddShopDrawerOpen = ref(false);

const selectedShopCode = ref("");
const searchQuery = ref("");
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "SHOP CODE",
    key: "SHOPCODE",
  },
  {
    title: "SHOP NAME",
    key: "SHOPNAME",
  },
  {
    title: "PLANT TYPE",
    key: "PLANTTYPE",
  },
  {
    title: "COUNT FLAG",
    key: "COUNTFLAG",
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
    const response = await $api("/master/shops", {
      params: {
        shop_code: searchQuery.value,
        shop_name: searchQuery.value,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    data.value = response.data;
    // console.log(response.data);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deletePart() {
  try {
    const result = await $api("/master/shops/" + selectedShopCode.value, {
      method: "DELETE",

      onResponseError({ response }) {
        toast.error("Failed to delete data");
        errors.value = response._data.errors;
      },
    });

    selectedShopCode.value = "";
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

function openDeleteDialog(partCode) {
  selectedShopCode.value = partCode;
  isDeleteDialogVisible.value = true;
}

async function openEditPartPage(partCode) {
  selectedShopCode.value = partCode;
  isAddShopDrawerOpen.value = true;
}

watch(
  () => isAddShopDrawerOpen.value,
  (newVal) => {
    if (!newVal) {
      // When the drawer is closed
      selectedShopCode.value = undefined;
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
          title: 'Shop',
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
        <!-- 👉 Search  -->
        <div style="inline-size: 15.625rem">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search"
            v-on:input="fetchData()"
          />
        </div>

        <!-- 👉 Export button -->
        <VBtn variant="tonal" color="secondary" prepend-icon="tabler-upload">
          Export
        </VBtn>

        <!-- 👉 Add button -->
        <VBtn
          prepend-icon="tabler-plus"
          @click="isAddShopDrawerOpen = !isAddShopDrawerOpen"
        >
          Add New Shop
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
      <template #item.partcode="{ item }">
        <div class="d-flex align-center">
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            >{{ item.shopcode }}</span
          >
        </div>
      </template>

      <!-- date -->
      <template #item.partname="{ item }">
        <div class="d-flex align-center">
          {{ item.shopname }}
        </div>
      </template>

      <!-- vendor -->
      <template #item.category="{ item }">
        <div class="d-flex align-center">
          {{ item.planttype }}
        </div>
      </template>

      <!-- unit price -->
      <template #item.minstock="{ item }">
        <div class="d-flex align-center">
          {{ item.countflag }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openEditPartPage(item.SHOPCODE)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn @click="openDeleteDialog(item.SHOPCODE)">
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

  <!-- Add Item Drawer -->
  <AddShopDrawer
    v-model:isDrawerOpen="isAddShopDrawerOpen"
    v-model:id="selectedShopCode"
    @submit="fetchData"
  />
</template>
