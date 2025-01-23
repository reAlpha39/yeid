<script setup>
import axios from "axios";
import moment from "moment";
import VueEasyLightbox from "vue-easy-lightbox";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "invControlMasterPart",
  },
});

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const isUpdateStockQtyDialogVisible = ref(false);
const barcodeDialogRef = ref(null);

const selectedPartCode = ref("");
// Data table options
const loading = ref(false);
const totalItems = ref(0);
const itemsPerPage = ref(10);
const page = ref(1);
const data = ref([]);
const searchQuery = ref("");
const sortBy = ref([{ key: "partcode", order: "asc" }]);
const categories = ["Machine", "Facility", "Jig", "Other"];
const vendors = ref([]);
const sortDesc = ref([]);
const selectedStatus = ref(null);
const appliedOptions = ref({});

const partCode = ref();
const partName = ref();
const spec = ref();
const brand = ref();
const category = ref();
const vendor = ref();
const address = ref();
const note = ref();
const usedParts = ref(false);
const minusParts = ref(false);
const orderParts = ref(false);

// State for lightbox
const isLightboxVisible = ref(false);
const imgsRef = ref([]);
const currentItem = ref(null);

// headers
const headers = [
  {
    title: "IMAGE",
    key: "partimage",
    sortable: false,
  },
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
    title: "SPECIFICATION",
    key: "specification",
  },
  {
    title: "BRAND",
    key: "brand",
  },
  {
    title: "ADDRESS",
    key: "address",
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
    key: "unitprice",
  },
  {
    title: "NOTE",
    key: "note",
  },
  {
    title: "REQUEST ORDER",
    key: "reqquotationdate",
  },
  {
    title: "ORDER",
    key: "orderdate",
  },
  {
    title: "P/O SENT",
    key: "posentdate",
  },
  {
    title: "ETD",
    key: "etddate",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

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

    const response = await $api("/master/part-list", {
      params: {
        search: searchQuery.value,
        part_code: partCode.value,
        part_name: partName.value,
        specification: spec.value,
        brand: brand.value,
        address: address.value,
        category: revCategoryType(category.value),
        vendor_code: vendor.value?.vendorcode,
        used_flag: usedParts.value ? "1" : "0",
        minus_flag: minusParts.value ? "1" : "0",
        order_flag: orderParts.value ? "1" : "0",
        note: note.value,
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
    path: "/inventory-control/master-part/add",
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

function revCategoryType(category) {
  switch (category) {
    case "Machine":
      return "M";
    case "Facility":
      return "F";
    case "Jig":
      return "J";
    case "Other":
      return "O";
    default:
      return "";
  }
}

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;

  var options = appliedOptions.value;
  try {
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
    const response = await axios.get("/api/master/part-inventory/export", {
      responseType: "blob",
      params: {
        search: searchQuery.value,
        part_code: partCode.value,
        part_name: partName.value,
        specification: spec.value,
        brand: brand.value,
        address: address.value,
        category: revCategoryType(category.value),
        vendor_code: vendor.value?.vendorcode,
        status: selectedStatus.value,
        used_flag: usedParts.value ? "1" : "0",
        minus_flag: minusParts.value ? "1" : "0",
        order_flag: orderParts.value ? "1" : "0",
        note: note.value,
        page: page.value,
        per_page: itemsPerPage.value,
        ...sortParams,
        ...options,
      },
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "inventory_parts.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

function getPartNamePrefix(item) {
  const status = (item.status || "").trim();
  const totalstock = parseStock(item.totalstock);
  const minstock = parseStock(item.minstock);

  // Only add prefix for stock alerts
  if (totalstock <= minstock) {
    return status === "O" ? "# " : "* ";
  }
  return "";
}

function parseStock(value) {
  return value ? parseFloat(value) : 0;
}

// Status color logic
function getStatusColor(item) {
  // Remove whitespace from status and check if empty
  const status = (item.status || "").trim();
  const posentdate = (item.posentdate || "").trim();
  const totalstock = parseStock(item.totalstock);
  const minstock = parseStock(item.minstock);

  // Check if item has PO sent date (order status)
  if (posentdate) {
    const etddate = (item.etddate || "").trim();
    if (etddate) {
      // Parse YYYYMMDD format using moment
      const etd = moment(etddate, "YYYYMMDD");
      const today = moment().startOf("day");

      return etd.isSameOrAfter(today) ? "status-1a" : "status-2a";
    }
  }

  // Check stock level status
  if (totalstock <= minstock) {
    return status === "O" ? "status-1b" : "status-2b";
  }

  return "status-default";
}

function getTextStyle(item) {
  const status = (item.status || "").trim();
  const totalstock = parseStock(item.totalstock);
  const minstock = parseStock(item.minstock);

  // Only apply bold style for stock alerts
  if (totalstock <= minstock && status !== "O") {
    return "font-weight-bold";
  }
  return "";
}

function getStockStyle(item) {
  const posentdate = (item.posentdate || "").trim();
  // Always bold the quantity for items with PO sent
  if (posentdate) {
    return "font-weight-bold";
  }
  return "";
}

const openBarCodeDialog = (partcode, partname) => {
  barcodeDialogRef.value.openDialog(partcode, partname);
};

// Function to show image in lightbox
const showImage = (item) => {
  if (item.partimage) {
    imgsRef.value = `/storage/${item.partimage}`;
    currentItem.value = {
      title: item.partcode + " - " + item.partname,
      src: imgsRef.value,
    };
    isLightboxVisible.value = true;
  }
};

// Function to close lightbox
const closeLightbox = () => {
  isLightboxVisible.value = false;
  currentItem.value = null;
};

const debouncedFetchData = debounce(fetchData, 500);

// Watch for search query changes
watch(
  [
    partCode,
    partName,
    spec,
    brand,
    address,
    category,
    vendor,
    note,
    usedParts,
    minusParts,
    orderParts,
  ],
  () => {
    page.value = 1; // Reset to first page when search changes
    debouncedFetchData();
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
          title: 'Inventory Control',
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
          v-if="$can('create', 'invControlMasterPart')"
          prepend-icon="tabler-plus"
          to="master-part/add"
        >
          Add New Part
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
                  v-model="category"
                  :items="categories"
                  clearable
                  clear-icon="tabler-x"
                  outlined
                  placeholder="Category"
                />
              </VCol>
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
                <AppTextField v-model="address" placeholder="Address" />
              </VCol>
              <VCol cols="3">
                <AppTextField v-model="note" placeholder="Note" />
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

    <!-- 👉 Datatable  -->
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
        <template #item.partimage="{ item }">
          <div class="d-flex justify-center align-center">
            <IconBtn v-if="item.partimage" @click="showImage(item)">
              <VIcon icon="tabler-camera" />
            </IconBtn>

            <VIcon v-else icon="tabler-camera-off" />
          </div>
        </template>

        <!-- part code -->
        <template #item.partcode="{ item }">
          <div class="d-flex align-center">
            <!-- <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              :class="getTextStyle(item)"
              ></span
            > -->
            {{ item.partcode }}
          </div>
        </template>

        <!-- part name -->
        <template #item.partname="{ item }">
          <div class="d-flex align-center">
            <!-- <span :class="getTextStyle(item)">
              {{ getPartNamePrefix(item) }}
            </span> -->
            {{ item.partname }}
          </div>
        </template>

        <!-- category -->
        <template #item.category="{ item }">
          <div class="d-flex align-center">
            {{ categoryType(item.category) }}
          </div>
        </template>

        <!-- stock quantity -->
        <template v-slot:header.totalstock> STOCK<br />QUANTITY </template>
        <template #item.totalstock="{ item }">
          <div class="d-flex align-center justify-start">
            <span :class="getStockStyle(item)">{{
              formatNumber(item.totalstock)
            }}</span>
          </div>
        </template>

        <!-- minimum stock -->
        <template v-slot:header.minstock> MINIMUM<br />STOCK </template>
        <template #item.minstock="{ item }">
          <div class="d-flex align-center justify-start">
            {{ formatNumber(item.minstock) }}
          </div>
        </template>

        <!-- unit price -->
        <template #item.unitprice="{ item }">
          {{ formatCurrency(item.currency, item.unitprice) }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-2">
            <!-- <div class="d-flex justify-center align-center">
            <div class="status-indicator mr-2" :class="getStatusColor(item)" />
          </div> -->

            <!-- Popup Menu -->
            <VMenu>
              <template v-slot:activator="{ props }">
                <VBtn
                  icon
                  variant="text"
                  v-bind="props"
                  size="small"
                  color="default"
                >
                  <VIcon icon="tabler-dots-vertical" />
                </VBtn>
              </template>

              <VList>
                <!-- Print Action -->
                <VListItem
                  @click="openBarCodeDialog(item.partcode, item.partname)"
                  density="compact"
                >
                  <template v-slot:prepend>
                    <VIcon icon="tabler-printer" size="small" />
                  </template>
                  <VListItemTitle>Print Barcode</VListItemTitle>
                </VListItem>

                <!-- Edit Action -->
                <VListItem
                  v-if="$can('update', 'invControlMasterPart')"
                  @click="openEditPartPage(item.partcode)"
                  density="compact"
                >
                  <template v-slot:prepend>
                    <VIcon icon="tabler-edit" size="small" />
                  </template>
                  <VListItemTitle>Edit</VListItemTitle>
                </VListItem>

                <!-- Update Action -->
                <VListItem
                  v-if="$can('update', 'invControlMasterPart')"
                  @click="openUpdateDialog(item.partcode)"
                  density="compact"
                >
                  <template v-slot:prepend>
                    <VIcon icon="tabler-adjustments" size="small" />
                  </template>
                  <VListItemTitle>Update</VListItemTitle>
                </VListItem>

                <!-- Delete Action -->
                <VListItem
                  v-if="$can('delete', 'invControlMasterPart')"
                  @click="openDeleteDialog(item.partcode)"
                  density="compact"
                >
                  <template v-slot:prepend>
                    <VIcon icon="tabler-trash" size="small" color="error" />
                  </template>
                  <VListItemTitle class="text-error">Delete</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
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

  <BarcodeDialog ref="barcodeDialogRef" />

  <VueEasyLightbox
    :visible="isLightboxVisible"
    :imgs="imgsRef"
    :index="0"
    :maxZoom="1"
    :minZoom="0.5"
    @hide="closeLightbox"
    @on-close="closeLightbox"
    :escDisabled="false"
  >
    <template #title>
      <div class="custom-title" v-if="currentItem">
        <div class="title-content">
          {{ currentItem.title }}
        </div>
      </div>
    </template>
  </VueEasyLightbox>
</template>

<style>
.status-indicator {
  width: 14px;
  height: 14px;
  border-radius: 50%;
}

.status-1b {
  background-color: #f87d02;
}

.status-2b {
  background-color: #fa0202;
}

.status-1a {
  background-color: #f2ea00;
}

.status-2a {
  background-color: #2d9cdb;
}

.status-default {
  background-color: #28c76f;
}
</style>
