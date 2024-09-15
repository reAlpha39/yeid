<script setup>
// import data from '@/views/demos/forms/tables/data-table/datatable';

// No need to repeat `https://localhost/api` now
const data = await $api("/invControl", {
  params: {
    startDate: "20240417",
    endDate: "20241231",
    jobCode: "I",
    limit: 0,
    orderBy: "jobdate",
    direction: "desc",
  },
});

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);
// const sortBy = ref();
// const orderBy = ref();

// const updateOptions = (options) => {
//   sortBy.value = options.sortBy[0]?.key;
//   orderBy.value = options.sortBy[0]?.order;
// };

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

onMounted(() => {
  // userList.value = JSON.parse(JSON.stringify(data));
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
        <VBtn
          prepend-icon="tabler-plus"
          to="create-inbound"
        >
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
          <div class="d-flex flex-column ms-3">
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
            {{ item.unitprice }}
          </div>
        </div>
      </template>

      <!-- unit price -->
      <template #item.total="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.currency }}
            {{ item.total }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="deleteItem(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <!-- 👉 Delete Dialog  -->
  <VDialog v-model="deleteDialog" max-width="500px">
    <VCard>
      <VCardTitle> Are you sure you want to delete this item? </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn color="error" variant="outlined" @click="closeDelete">
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="deleteItemConfirm">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>
