<script setup>
import axios from "axios";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "inventoryInbound",
  },
});

const { can } = usePermissions();
const toast = useToast();

const isDeleteDialogVisible = ref(false);
const recordIdToDelete = ref(0);

const now = new Date();
const oneYearAgo = new Date(now);
oneYearAgo.setFullYear(now.getFullYear() - 1);

const selectedDate = ref(null);
const vendors = ref([]);
const selectedVendors = ref();
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];
const currency = ref();

// Data table options
const loading = ref(false);
const totalItems = ref(0);
const itemsPerPage = ref(10);
const page = ref(1);
const data = ref([]);
const searchQuery = ref("");
const sortBy = ref([{ key: "jobdate", order: "asc" }]);
const sortDesc = ref([]);

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
    key: "vendorcode",
  },
  {
    title: "BRAND",
    key: "brand",
  },
  {
    title: "SPECIFICATION",
    key: "specification",
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
    title: "NOTE",
    key: "note",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}${month}${day}`;
};

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

    const response = await $api("/invControl", {
      params: {
        search: searchQuery.value,
        startDate: selectedDate.value ?? formatDate(oneYearAgo),
        endDate: selectedDate.value ?? formatDate(now),
        jobCode: "I",
        limit: 0,
        orderBy: "jobdate",
        direction: "desc",
        vendorcode: selectedVendors.value?.vendorcode,
        currency: currency.value,
        page: page.value,
        per_page: itemsPerPage.value,
        ...sortParams,
        ...options,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
        // errors.value = response._data.errors;
      },
    });

    // Update data and pagination info
    data.value = response.data;
    totalItems.value = response.pagination.total;
  } catch (err) {
    // toast.error("Failed to fetch data");
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

  // Fetch the data with new options
  fetchData(options);
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

async function fetchDataVendor(id) {
  try {
    if (id) {
      const response = await $api("/master/vendors/" + encodeURIComponent(id));

      selectedVendors.value = response.data;
      selectedVendors.value.title =
        response.data.vendorcode + " | " + response.data.vendorname;
    } else {
      const response = await $api("/master/vendors");

      vendors.value = response.data;

      vendors.value.forEach((data) => {
        data.title = data.vendorcode + " | " + data.vendorname;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/invControl/export", {
      responseType: "blob",
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
      params: {
        search: searchQuery.value,
        startDate: selectedDate.value ?? formatDate(oneYearAgo),
        endDate: selectedDate.value ?? formatDate(now),
        jobCode: "I",
        limit: 0,
        orderBy: "jobdate",
        direction: "desc",
        vendorcode: selectedVendors.value?.vendorcode,
        currency: currency.value,
      },
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "inventory_inbounds.xlsx";
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
  fetchData();
  fetchDataVendor();
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
          <AppDateTimePicker
            v-model="selectedDate"
            placeholder="Select Date"
            :config="{ dateFormat: 'Ymd' }"
            append-inner-icon="tabler-calendar"
            clearable
            clear-icon="tabler-x"
            @update:modelValue="fetchData()"
          />
        </VCol>
        <!-- 👉 Select Plan -->
        <VCol cols="12" sm="4">
          <AppAutocomplete
            v-model="selectedVendors"
            placeholder="Select Vendor"
            item-title="title"
            :items="vendors"
            return-object
            clearable
            clear-icon="tabler-x"
            outlined
            @update:modelValue="fetchData()"
          />
        </VCol>
        <!-- 👉 Select Status -->
        <VCol cols="12" sm="4">
          <AppSelect
            v-model="currency"
            :items="currencies"
            placeholder="Select Currency"
            clearable
            clear-icon="tabler-x"
            @update:modelValue="fetchData()"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDivider />

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
          v-if="can('create', 'inventoryInbound')"
          prepend-icon="tabler-plus"
          to="create-inbound"
        >
          Create In-Bound
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <div class="sticky-actions-wrapper">
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="totalItems"
        :loading="loading"
        :headers="headers"
        :items="data"
        :sort-by="sortBy"
        :sort-desc="sortDesc"
        class="text-no-wrap"
        @update:options="handleOptionsUpdate"
        height="562"
      >
        <!-- part name -->
        <template #item.partcode="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.partname }}</span
              >
              <small>{{ item.partcode }}</small>
            </div>
          </div>
        </template>

        <!-- unit price -->
        <template #item.currency="{ item }">
          {{ formatCurrency(item.currency, item.unitprice) }}
        </template>

        <template #item.quantity="{ item }">
          {{ parseInt(item?.quantity ?? 0) }}
        </template>

        <!-- unit price -->
        <template #item.total="{ item }">
          {{ formatCurrency(item.currency, item.total) }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <IconBtn
              v-if="$can('delete', 'inventoryInbound')"
              @click="openDeleteDialog(item)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </div>

    <!-- Pagination Controls -->
    <template #bottom>
      <TablePagination
        v-model:page="page"
        :items-per-page="itemsPerPage"
        :total-items="totalItems"
      />
    </template>
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
