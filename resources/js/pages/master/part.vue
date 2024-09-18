<script setup>
import { useToast } from "vue-toastification";

const toast = useToast();

const isDeleteDialogVisible = ref(false);
const recordIdToDelete = ref(0);

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "PART CODE",
    key: "PARTCODE",
  },
  {
    title: "PART NAME",
    key: "PARTNAME",
  },
  {
    title: "CATEGORY",
    key: "CATEGORY",
  },
  {
    title: "STOCK QUANTITY",
    key: "LASTSTOCKNUMBER",
  },
  {
    title: "MINIMUM STOCK",
    key: "MINSTOCK",
  },
  {
    title: "UNIT PRICE",
    key: "unitprice",
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
    const response = await $api("/master-part-list", {
      params: {
        search: "",
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

async function deleteRecord() {
  try {
    const result = await $api("/deleteRecord", {
      method: "DELETE",
      body: {
        record_id: parseInt(recordIdToDelete.value),
      },

      onResponseError({ response }) {
        toast.error("Failed to delete data");
        errors.value = response._data.errors;
      },
    });

    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

function openDeleteDialog(recordId) {
  isDeleteDialogVisible.value = true;
  recordIdToDelete.value = recordId.recordid;
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
          <AppTextField v-model="searchQuery" placeholder="Search" />
        </div>

        <!-- 👉 Export button -->
        <VBtn variant="tonal" color="secondary" prepend-icon="tabler-upload">
          Export
        </VBtn>

        <!-- 👉 Add button -->
        <VBtn prepend-icon="tabler-plus" to="add-part"> Add New Part </VBtn>
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
      @update:options="updateOptions"
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
          {{ item.category }}
        </div>
      </template>

      <!-- unit price -->
      <template #item.minstock="{ item }">
        <div class="d-flex align-center">
          {{ item.minstock }}
        </div>
      </template>

      <!-- unit price -->
      <template #item.unitprice="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.CURRENCY }}
            {{ item.UNITPRICE.toLocaleString() }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openDeleteDialog(item)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn @click="openDeleteDialog(item)">
            <VIcon icon="tabler-adjustments" />
          </IconBtn>
          <IconBtn @click="openDeleteDialog(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <!-- 👉 Delete Dialog  -->
  <VDialog v-model="isDeleteDialogVisible" max-width="500px">
    <VCard>
      <VCardTitle> Are you sure you want to delete this item? </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="isDeleteDialogVisible = !isDeleteDialogVisible"
        >
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="deleteRecord()">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>
