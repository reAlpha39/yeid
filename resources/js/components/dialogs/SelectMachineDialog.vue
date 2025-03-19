<script setup>
const search = ref("");
const selectedMachine = ref();
const selectedMaker = ref();
const shop = ref();
const line = ref();

const makers = ref([]);
const shops = ref([]);
const data = ref([]);
const loading = ref(false);

// For server-side pagination
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

// For VDataTableServer sorting
const sortBy = ref([{ key: "machineno", order: "asc" }]);
const sortDesc = ref([]);
const appliedOptions = ref({});

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  items: {
    type: Object,
    required: false,
  },
  shopcode: {
    type: String,
    required: false,
  },
});

async function fetchMachines(options = {}) {
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

    let response = await $api("/master/machines", {
      params: {
        search: search.value,
        maker: selectedMaker.value?.makercode,
        shopcode: shop.value?.shopcode,
        linecode: line.value,
        page: options.page || pagination.value.current_page,
        per_page: options.itemsPerPage || pagination.value.per_page,
        ...sortParams,
      },
    });

    // Update data and pagination from response
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
  fetchMachines(options);
}

async function fetchDataMaker(id) {
  try {
    if (id) {
      const response = await $api("/master/makers/" + encodeURIComponent(id), {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      });

      selectedMaker.value = response.data;
      selectedMaker.value =
        response.data.makercode + " | " + response.data.makername;
    } else {
      const response = await $api("/master/makers", {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      });

      makers.value = response.data;

      makers.value.forEach((maker) => {
        maker.title = maker.makercode + " | " + maker.makername;
      });
    }
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataShop(id) {
  try {
    if (id) {
      const response = await $api("/master/shops/" + encodeURIComponent(id), {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      });
      shop.value = response.data;
      shop.value.title =
        response.data.shopcode + " | " + response.data.shopname;
    } else {
      const response = await $api("/master/shops", {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      });

      shops.value = response.data;

      shops.value.forEach((data) => {
        data.title = data.shopcode + " | " + data.shopname;
      });
    }
  } catch (err) {
    console.log(err);
  }
}

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const handleItemClick = (item) => {
  // Process the item data as needed
  console.log("Selected item:", item);

  emit("update:isDialogVisible", false);
  emit("submit", item);
};

// Create column headers
const headers = [
  {
    title: "Machine",
    key: "machinename",
  },
  {
    title: "Plant",
    key: "plantcode",
  },
  {
    title: "Line",
    key: "linecode",
  },
  {
    title: "Model",
    key: "modelname",
  },
  {
    title: "Maker",
    key: "makername",
  },
  {
    title: "Shop",
    key: "shopname",
  },
  {
    title: "Action",
    key: "actions",
    sortable: false,
  },
];

const debouncedFetchData = debounce(() => {
  pagination.value.current_page = 1; // Reset to page 1 when filters change
  fetchMachines({
    page: 1,
    itemsPerPage: pagination.value.per_page,
    sortBy: sortBy.value,
    sortDesc: sortDesc.value,
  });
}, 500);

watch([search, selectedMaker, shop, line], () => {
  debouncedFetchData();
});

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedMachine.value = props.items;

      // Reset pagination to first page when dialog opens
      pagination.value.current_page = 1;

      // Reset sort to default
      sortBy.value = [{ key: "machineno", order: "asc" }];

      fetchMachines({
        page: 1,
        itemsPerPage: pagination.value.per_page,
        sortBy: sortBy.value,
      });
      fetchDataMaker();
      fetchDataShop();

      if (props.shopcode) {
        fetchDataShop(props.shopcode);
      }

      console.log("Dialog opened with items:", props.items);
    }
  }
);
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
        <h4 class="text-h4 text-center mb-2">Add Machine</h4>
        <p class="text-body-1 text-center mb-2">Select Machine</p>
      </VCardText>

      <VRow class="pb-4">
        <VCol>
          <AppTextField
            v-model="search"
            label="Search"
            placeholder="Search"
            variant="outlined"
          />
        </VCol>

        <VCol>
          <AppAutocomplete
            v-model="shop"
            label="Shop"
            placeholder="Select shop"
            item-title="title"
            :items="shops"
            outlined
            return-object
            :clearable="props.shopcode === undefined"
            :readonly="props.shopcode !== undefined"
          />
        </VCol>

        <VCol>
          <AppAutocomplete
            v-model="selectedMaker"
            label="Maker"
            placeholder="Select maker"
            item-title="title"
            :items="makers"
            return-object
            outlined
            clearable
          />
        </VCol>

        <VCol>
          <AppTextField
            v-model="line"
            label="Line"
            placeholder="Select line"
            variant="outlined"
          />
        </VCol>
      </VRow>

      <VDivider />

      <div class="sticky-actions-wrapper">
        <VDataTableServer
          v-model:items-per-page="pagination.per_page"
          v-model:page="pagination.current_page"
          :items-length="pagination.total"
          :items="data"
          :headers="headers"
          :loading="loading"
          :sort-by="sortBy"
          class="text-no-wrap"
          @update:options="handleOptionsUpdate"
          height="560"
        >
          <!-- Machine column with machine number as small text -->
          <template #item.machinename="{ item }">
            <div class="d-flex flex-column">
              <span style="font-weight: 500">{{ item.machinename }}</span>
              <small>{{ item.machineno }}</small>
            </div>
          </template>

          <!-- Maker column with maker code as small text -->
          <template #item.makername="{ item }">
            <div class="d-flex flex-column">
              <span style="font-weight: 500">{{ item.makername }}</span>
              <small>{{ item.makercode }}</small>
            </div>
          </template>

          <!-- Shop column with shop code as small text -->
          <template #item.shopname="{ item }">
            <div class="d-flex flex-column">
              <span style="font-weight: 500">{{ item.shopname }}</span>
              <small>{{ item.shopcode }}</small>
            </div>
          </template>

          <!-- Action column -->
          <template #item.actions="{ item }">
            <VCol cols="11" md="1">
              <a @click.prevent="handleItemClick(item)" style="cursor: pointer"
                >Select</a
              >
            </VCol>
          </template>
        </VDataTableServer>
      </div>
    </VCard>
  </VDialog>
</template>
