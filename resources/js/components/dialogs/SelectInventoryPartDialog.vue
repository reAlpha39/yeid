<script setup>
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];
const vendors = ref();

// Server-side data and pagination
const data = ref([]);
const loading = ref(false);
const pagination = ref({
  total: 0,
  per_page: 10,
  current_page: 1,
  last_page: 1,
  from: null,
  to: null,
  next_page_url: null,
  prev_page_url: null,
});

// Sorting
const sortBy = ref([{ key: "partcode", order: "asc" }]);
const sortDesc = ref([]);
const appliedOptions = ref({});

// Search filters
const searchPartCode = ref("");
const searchPartName = ref("");
const selectedVendors = ref();
const currency = ref();
const specTf = ref();
const brandTf = ref();

// Table headers
const headers = [
  { title: "PART", key: "partname", sortable: true },
  { title: "BRAND", key: "brand", sortable: true },
  { title: "SPECIFICATION", key: "specification", sortable: true },
  { title: "CURRENCY", key: "currency", sortable: true },
  { title: "UNIT PRICE", key: "unitprice", sortable: true },
  { title: "ACTION", key: "actions", sortable: false },
];

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const handleItemClick = (item) => {
  emit("update:isDialogVisible", false);
  emit("submit", item);
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

      const sortDirection =
        typeof options.sortBy[0] === "object"
          ? options.sortBy[0].order
          : options.sortDesc?.[0]
          ? "desc"
          : "asc";

      sortParams.sortBy = JSON.stringify({
        key: sortColumn,
        order: sortDirection,
      });
    } else if (sortBy.value[0]) {
      // Use current sort if no sort in options
      sortParams.sortBy = JSON.stringify({
        key: sortBy.value[0].key,
        order: sortBy.value[0].order,
      });
    }

    const response = await $api("/getPartInfo", {
      params: {
        partCode: searchPartCode.value,
        partName: searchPartName.value,
        vendorcode: selectedVendors.value?.vendorcode,
        currency: currency.value,
        spec: specTf.value,
        brand: brandTf.value,
        page: options.page || pagination.value.current_page,
        per_page: options.itemsPerPage || pagination.value.per_page,
        ...sortParams,
      },
    });

    data.value = response.data;
    pagination.value = response.pagination;
  } catch (err) {
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
  pagination.value.current_page = options.page;
  pagination.value.per_page = options.itemsPerPage;

  appliedOptions.value = options;

  // Fetch the data with new options
  fetchData(options);
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

const debouncedFetchData = debounce(() => {
  pagination.value.current_page = 1; // Reset to first page when filters change
  fetchData({
    page: 1,
    itemsPerPage: pagination.value.per_page,
    sortBy: sortBy.value,
    sortDesc: sortDesc.value,
  });
}, 500);

watch(
  [searchPartCode, searchPartName, specTf, brandTf, selectedVendors, currency],
  () => {
    debouncedFetchData();
  }
);

onMounted(() => {
  fetchData({
    page: pagination.value.current_page,
    itemsPerPage: pagination.value.per_page,
    sortBy: sortBy.value,
  });
  fetchDataVendor();
});
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 1200"
    @update:model-value="dialogVisibleUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Select Part</h4>
        <p class="text-body-1 text-center mb-6">You can only select one part</p>
      </VCardText>

      <VRow>
        <VCol md="4">
          <AppTextField
            v-model="searchPartCode"
            placeholder="Search part code"
            variant="outlined"
          />
        </VCol>
        <VCol md="4">
          <AppTextField
            v-model="searchPartName"
            placeholder="Search part name"
            variant="outlined"
          />
        </VCol>
        <VCol md="4">
          <AppAutocomplete
            v-model="selectedVendors"
            placeholder="Select Vendor"
            item-title="title"
            :items="vendors"
            return-object
            clearable
            clear-icon="tabler-x"
            outlined
          />
        </VCol>
      </VRow>

      <VRow class="pb-4">
        <VCol md="4">
          <AppTextField
            v-model="brandTf"
            placeholder="Brand"
            variant="outlined"
          />
        </VCol>
        <VCol md="4">
          <AppTextField
            v-model="specTf"
            placeholder="Spec"
            variant="outlined"
          />
        </VCol>
        <VCol md="4">
          <AppSelect
            v-model="currency"
            :items="currencies"
            placeholder="Select Currency"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>

      <VDivider />

      <div class="sticky-actions-wrapper">
        <VDataTableServer
          v-model:items-per-page="pagination.per_page"
          v-model:page="pagination.current_page"
          :items-length="pagination.total"
          :loading="loading"
          :headers="headers"
          :items="data"
          :sort-by="sortBy"
          class="text-no-wrap"
          @update:options="handleOptionsUpdate"
          height="562"
        >
          <!-- Part name column with part code as small text -->
          <template #item.partname="{ item }">
            <div class="d-flex flex-column">
              <span style="font-weight: 500">{{ item.partname }}</span>
              <small>{{ item.partcode }}</small>
            </div>
          </template>

          <!-- Unit price column with formatted number -->
          <template #item.unitprice="{ item }">
            {{ item.unitprice.toLocaleString() }}
          </template>

          <!-- Action column -->
          <template #item.actions="{ item }">
            <a @click.prevent="handleItemClick(item)" style="cursor: pointer">
              Select
            </a>
          </template>
        </VDataTableServer>
      </div>
    </VCard>
  </VDialog>
</template>
