<script setup>
import axios from "axios";
import moment from "moment";
import VueEasyLightbox from "vue-easy-lightbox";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "part",
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
const sortBy = ref([]);
const sortDesc = ref([]);

// State for lightbox
const isLightboxVisible = ref(false);
const imgsRef = ref([]);
const currentItem = ref(null);

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
    key: "unitprice",
  },
  {
    title: "PART IMAGE",
    key: "partimage",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
    align: "center fixed",
    class: "fixed",
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
        category: "",
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
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/master/part-list/export", {
      responseType: "blob",
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
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

let idr = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
});

let usd = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "USD",
});

let sgd = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "SGD",
});

let jpy = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "JPY",
});

function formatCurrency(currency, price) {
  switch (currency) {
    case "IDR":
      return idr.format(parseFloat(price));
    case "USD":
      return usd.format(parseFloat(price));
    case "SDG":
      return sgd.format(parseFloat(price));
    case "JPY":
      return jpy.format(parseFloat(price));
    default:
      return "-";
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

function formatNumber(value) {
  if (!value) return "0";
  return parseFloat(value).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
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

onMounted(() => {
  fetchData();
});

// Watch for search query changes
watch(searchQuery, () => {
  page.value = 1; // Reset to first page when search changes
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
          v-if="$can('create', 'part')"
          prepend-icon="tabler-plus"
          to="part/add"
        >
          Add New Part
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <!-- 👉 Datatable  -->
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
    >
      <!-- part code -->
      <template #item.partcode="{ item }">
        <div class="d-flex align-center">
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            :class="getTextStyle(item)"
            >{{ item.partcode }}</span
          >
        </div>
      </template>

      <!-- part name -->
      <template #item.partname="{ item }">
        <div class="d-flex align-center">
          <span :class="getTextStyle(item)">
            {{ getPartNamePrefix(item) }}{{ item.partname }}
          </span>
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

      <template #item.partimage="{ item }">
        <VBtn
          v-if="item.partimage"
          variant="text"
          color="secondary"
          @click="showImage(item)"
          size="small"
        >
          Lihat gambar
        </VBtn>
        <text v-else>Foto tidak<br />tersedia</text>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-2">
          <div class="d-flex justify-center align-center">
            <div class="status-indicator mr-2" :class="getStatusColor(item)" />
          </div>

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
                v-if="$can('update', 'part')"
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
                v-if="$can('update', 'part')"
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
                v-if="$can('delete', 'part')"
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
table > tbody > tr > td.fixed:nth-last-child(1),
table > thead > tr > th.fixed:nth-last-child(1) {
  position: sticky !important;
  position: -webkit-sticky !important;
  right: 0;
  z-index: 9998;
  background: white;
  -webkit-box-shadow: -1px 0px 3px -1px rgba(0, 0, 0, 0.19);
  -moz-box-shadow: -1px 0px 3px -1px rgba(0, 0, 0, 0.19);
  box-shadow: -1px 0px 3px -1px rgba(0, 0, 0, 0.19);
}

table > thead > tr > th.fixed:nth-last-child(1) {
  z-index: 9999;
}

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

:deep(.v-data-table) {
  .text-no-wrap {
    white-space: nowrap;
  }
}
</style>
