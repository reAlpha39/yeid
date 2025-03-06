<script setup>
import axios from "axios";
import { ref } from "vue";
import { useToast } from "vue-toastification";
const { can } = usePermissions();

definePage({
  meta: {
    action: "view",
    subject: "invControlOutbound",
  },
});

const toast = useToast();

const isDeleteDialogVisible = ref(false);
const recordIdToDelete = ref(0);

const isUpdateStockQtyDialogVisible = ref(false);
const selectedRecordId = ref("");
const selectedPartCode = ref("");
const selectedMachineNo = ref("");
const selectedQuantity = ref(0);

const now = new Date();
const oneYearAgo = new Date(now);
oneYearAgo.setFullYear(now.getFullYear() - 1);

const selectedVendors = ref();

// Data table options
const loading = ref(false);
const totalItems = ref(0);
const itemsPerPage = ref(10);
const page = ref(1);
const data = ref([]);
const sortBy = ref([{ key: "jobdate", order: "asc" }]);
const vendors = ref([]);
const sortDesc = ref([]);
const appliedOptions = ref({});

const partCode = ref();
const partName = ref();
const spec = ref();
const brand = ref();
const vendor = ref();
const note = ref();
const usedParts = ref(false);
const minusParts = ref(false);
const orderParts = ref(false);
const selectedStartDate = ref(null);
const selectedEndDate = ref(null);

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
    title: "EMPLOYEE",
    key: "employeecode",
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
        start_date: selectedStartDate.value,
        end_date: selectedEndDate.value,
        part_code: partCode.value,
        part_name: partName.value,
        brand: brand.value,
        specification: spec.value,
        vendor_code: vendor.value?.vendorcode,
        note: note.value,
        used_flag: usedParts.value ? "1" : "0",
        minus_flag: minusParts.value ? "1" : "0",
        order_flag: orderParts.value ? "1" : "0",
        job_code: "O",
        limit: 0,
        orderBy: "jobdate",
        direction: "desc",
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

  appliedOptions.value = options;

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

function openDeleteDialog(item) {
  isDeleteDialogVisible.value = true;
  recordIdToDelete.value = item.recordid;
}

function openAdjustQtyDialog(item) {
  selectedRecordId.value = item.recordid;
  selectedPartCode.value = item.partcode;
  selectedMachineNo.value = item.machineno;
  selectedQuantity.value = formatNumber(item.quantity);
  isUpdateStockQtyDialogVisible.value = true;
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

  var options = appliedOptions.value;
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

    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/invControl/export", {
      responseType: "blob",
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
      params: {
        start_date: selectedStartDate.value,
        end_date: selectedEndDate.value,
        part_code: partCode.value,
        part_name: partName.value,
        brand: brand.value,
        specification: spec.value,
        vendor_code: vendor.value?.vendorcode,
        note: note.value,
        used_flag: usedParts.value ? "1" : "0",
        minus_flag: minusParts.value ? "1" : "0",
        order_flag: orderParts.value ? "1" : "0",
        job_code: "O",
        limit: 0,
        orderBy: "jobdate",
        direction: "desc",
        page: page.value,
        per_page: itemsPerPage.value,
        ...sortParams,
        ...options,
      },
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "inventory_outbound.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

const debouncedFetchData = debounce(fetchData, 500);

watch(
  [
    partCode,
    partName,
    spec,
    brand,
    vendor,
    note,
    usedParts,
    minusParts,
    orderParts,
    selectedStartDate,
    selectedEndDate,
  ],
  () => {
    page.value = 1; // Reset to first page when search changes
    debouncedFetchData();
  }
);

onMounted(() => {
  selectedStartDate.value = formatDate(oneYearAgo);
  selectedEndDate.value = formatDate(now);

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
          title: 'Inventory Out-Bound',
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
          v-if="can('create', 'invControlOutbound')"
          prepend-icon="tabler-plus"
          to="create-outbound"
        >
          Create Out-Bound
        </VBtn>
      </div>
    </VCardText>

    <VCard class="mx-6" variant="outlined">
      <VExpansionPanels>
        <VExpansionPanel elevation="0">
          <VExpansionPanelTitle>
            <template v-slot:default="{ expanded }">
              <VRow no-gutters>
                <VCol class="d-flex justify-start" cols="4"> Filter </VCol>
              </VRow>
            </template>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <VRow>
              <VCol cols="3">
                <AppTextField v-model="partCode" placeholder="Part Code" />
              </VCol>
              <VCol cols="3">
                <AppTextField v-model="partName" placeholder="Part Name" />
              </VCol>
              <VCol cols="3">
                <AppTextField v-model="spec" placeholder="Spec" />
              </VCol>
              <VCol cols="3">
                <AppTextField v-model="brand" placeholder="Brand" />
              </VCol>
            </VRow>
            <VRow>
              <VCol cols="3">
                <AppAutocomplete
                  v-model="vendor"
                  :items="vendors"
                  return-object
                  clearable
                  clear-icon="tabler-x"
                  outlined
                  placeholder="Vendor"
                />
              </VCol>
              <VCol cols="3">
                <AppTextField v-model="note" placeholder="Note" />
              </VCol>
              <VCol cols="3">
                <AppDateTimePicker
                  v-model="selectedStartDate"
                  placeholder="Start Date"
                  :config="{ dateFormat: 'Ymd' }"
                  append-inner-icon="tabler-calendar"
                />
              </VCol>
              <VCol cols="3">
                <AppDateTimePicker
                  v-model="selectedEndDate"
                  placeholder="End Date"
                  :config="{ dateFormat: 'Ymd' }"
                  append-inner-icon="tabler-calendar"
                />
              </VCol>
            </VRow>
            <VRow>
              <VCol cols="2">
                <VCheckbox
                  class="pr-7"
                  label="Used Parts"
                  v-model="usedParts"
                />
              </VCol>
              <VCol cols="2">
                <VCheckbox
                  class="pr-7"
                  label="Minus Parts"
                  v-model="minusParts"
                />
              </VCol>
              <VCol cols="2">
                <VCheckbox
                  class="pr-7"
                  label="Order Parts"
                  v-model="orderParts"
                />
              </VCol>
            </VRow>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    </VCard>

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
          <div class="d-flex align-center">
            <div class="d-flex flex-row">
              {{ formatCurrency(item.currency, item.unitprice) }}
            </div>
          </div>
        </template>

        <template #item.quantity="{ item }">
          {{ parseInt(item?.quantity ?? 0) }}
        </template>

        <!-- unit price -->
        <template #item.total="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-row">
              {{ formatCurrency(item.currency, item.total) }}
            </div>
          </div>
        </template>

        <template #item.employeecode="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ item.employeename }}</span
              >
              <small>{{ item.employeecode }}</small>
            </div>
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <IconBtn
              v-if="$can('update', 'invControlOutbound')"
              @click="openAdjustQtyDialog(item)"
            >
              <VIcon icon="tabler-adjustments" />
            </IconBtn>
            <IconBtn
              v-if="$can('delete', 'invControlOutbound')"
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

  <UpdateOutboundQtyDialog
    v-model:isDialogVisible="isUpdateStockQtyDialogVisible"
    v-model:recordId="selectedRecordId"
    v-model:partCode="selectedPartCode"
    v-model:machineNo="selectedMachineNo"
    v-model:quantity="selectedQuantity"
    @submit="fetchData"
  />
</template>
