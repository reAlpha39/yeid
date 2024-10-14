<script setup>
import { useToast } from "vue-toastification";

const toast = useToast();

const isDeleteDialogVisible = ref(false);
const recordIdToDelete = ref(0);

const now = new Date();

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "PART",
    key: "partcode",
  },
  {
    title: "DATE",
    key: "jobdate",
  },
  {
    title: "VENDOR",
    key: "vendor",
  },
  {
    title: "UNIT PRICE",
    key: "currency",
  },
  {
    title: "QTY",
    key: "quantity",
  },
  {
    title: "TOTAL PRICE",
    key: "total",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

// data table
const data = ref([]);

const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}${month}${day}`;
};

async function fetchData() {
  try {
    const response = await $api("/invControl", {
      params: {
        startDate: "20240101",
        endDate: formatDate(now),
        jobCode: "I",
        limit: 0,
        orderBy: "jobdate",
        direction: "desc",
      },
      onResponseError({ response }) {
        toast.error("Failed to fetch data");
        errors.value = response._data.errors;
      },
    });

    data.value = response;
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
      class="px-0 pb-2 pt-0 inventory-control-inventory-inbound"
      :items="[
        {
          title: 'Inventory Control',
          class: 'text-h4',
        },
        {
          title: 'Inventory In-Bound',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <!-- 👉 products -->
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Filters</VCardTitle>
    </VCardItem>

    <VCardText>
      <VRow>
        <!-- 👉 Select Role -->
        <VCol cols="12" sm="4">
          <AppSelect
            v-model="selectedRole"
            placeholder="Select Date"
            :items="roles"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
        <!-- 👉 Select Plan -->
        <VCol cols="12" sm="4">
          <AppSelect
            v-model="selectedPlan"
            placeholder="Select Vendor"
            :items="plans"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
        <!-- 👉 Select Status -->
        <VCol cols="12" sm="4">
          <AppSelect
            v-model="selectedStatus"
            placeholder="Select Currency"
            :items="status"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDivider />

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
          <AppTextField v-model="searchQuery" placeholder="Search User" />
        </div>

        <!-- 👉 Export button -->
        <VBtn variant="tonal" color="secondary" prepend-icon="tabler-upload">
          Export
        </VBtn>

        <!-- 👉 Add button -->
        <VBtn prepend-icon="tabler-plus" to="create-inbound">
          Create In-Bound
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
      @update:options="updateOptions"
    >
      <!-- part name -->
      <template #item.partcode="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column ms-3">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.partname }}</span
            >
            <small>{{ item.partcode }}</small>
          </div>
        </div>
      </template>

      <!-- date -->
      <template #item.date="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column ms-3">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.brand }}</span
            >
            <small>{{ item.vendorcode }}</small>
          </div>
        </div>
      </template>

      <!-- vendor -->
      <template #item.vendor="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.brand }}</span
            >
            <small>{{ item.vendorcode }}</small>
          </div>
        </div>
      </template>

      <!-- unit price -->
      <template #item.currency="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.currency }}
            {{ item.unitprice.toLocaleString() }}
          </div>
        </div>
      </template>

      <!-- unit price -->
      <template #item.total="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.currency }}
            {{ item.total.toLocaleString() }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
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
      <VCardTitle class="text-center">
        Are you sure you want to delete this item?
      </VCardTitle>

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
