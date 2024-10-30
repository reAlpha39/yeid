<script setup>
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const isUpdateStockQtyDialogVisible = ref(false);

const selectedPartCode = ref("");
const searchQuery = ref("");
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "PART CODE",
    key: "partcode",
  },
  {
    title: "PART NAME",
    key: "partname",
  },
  {
    title: "CATEGORY",
    key: "category",
  },
  {
    title: "STOCK QUANTITY",
    key: "totalstock",
  },
  {
    title: "MINIMUM STOCK",
    key: "minstock",
  },
  {
    title: "UNIT PRICE",
    key: "unitprices",
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
    const response = await $api("/master/part-list", {
      params: {
        search: searchQuery.value,
        category: "",
      },
      onResponseError({ response }) {
        toast.error("Failed to fetch data");
        errors.value = response._data.errors;
      },
    });

    data.value = response.data;
    // console.log(data.value);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deletePart() {
  try {
    const result = await $api("/master/delete-part", {
      method: "DELETE",
      body: {
        part_code: selectedPartCode.value,
      },

      onResponseError({ response }) {
        toast.error("Failed to delete data");
        errors.value = response._data.errors;
      },
    });

    selectedPartCode.value = "";
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

function openDeleteDialog(partCode) {
  selectedPartCode.value = partCode;
  isDeleteDialogVisible.value = true;
}

function openUpdateDialog(partCode) {
  selectedPartCode.value = partCode;
  isUpdateStockQtyDialogVisible.value = true;
}

async function openEditPartPage(partCode) {
  // selectedPartCode.value = partCode;
  await router.push({
    path: "/master/part/add",
    query: { part_code: partCode },
  });
}

function categoryType(category) {
  switch (category) {
    case "M":
      return "Machine";
    case "F":
      return "Facility";
    case "J":
      return "Jig";
    case "O":
      return "Other";
    default:
      return "-";
  }
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const response = await axios.get("/api/master/part-list/export", {
      responseType: "blob",
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
          title: 'Part',
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
       <VBtn
          variant="tonal"
          prepend-icon="tabler-upload"
          @click="handleExport"
          :loading="loadingExport"
        >
          Export
        </VBtn>

        <!-- 👉 Add button -->
        <VBtn prepend-icon="tabler-plus" to="part/add"> Add New Part </VBtn>
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
            >{{ item.partcode }}</span
          >
        </div>
      </template>

      <!-- date -->
      <template #item.partname="{ item }">
        <div class="d-flex align-center">
          {{ item.partname }}
        </div>
      </template>

      <!-- vendor -->
      <template #item.category="{ item }">
        <div class="d-flex align-center">
          {{ categoryType(item.category) }}
        </div>
      </template>

      <!-- unit price -->
      <template #item.minstock="{ item }">
        <div class="d-flex align-center">
          {{ item.minstock }}
        </div>
      </template>

      <!-- unit price -->
      <template #item.unitprices="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.currency }}
            {{ item.unitprice.toLocaleString() }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openEditPartPage(item.partcode)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn @click="openUpdateDialog(item.partcode)">
            <VIcon icon="tabler-adjustments" />
          </IconBtn>
          <IconBtn @click="openDeleteDialog(item.partcode)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <UpdatePartStockQtyDialog
    v-model:isDialogVisible="isUpdateStockQtyDialogVisible"
    v-model:id="selectedPartCode"
    @submit="fetchData"
  />

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
