<script setup>
const search = ref("");
const selectedMachine = ref();
const selectedMaker = ref();
const shop = ref();
const line = ref();

const makers = ref([]);
const shops = ref([]);
const data = ref([]);
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
const sortBy = ref({ key: "machineno", order: "asc" });

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

async function fetchMachines() {
  try {
    console.log(selectedMaker.value);
    let response = await $api("/master/machines", {
      params: {
        search: search.value,
        maker: selectedMaker.value?.makercode,
        shopcode: shop.value?.shopcode,
        linecode: line.value,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        sortBy: JSON.stringify(sortBy.value),
      },
    });

    // Update data and pagination from response
    data.value = response.data;
    pagination.value = response.pagination;
  } catch (err) {
    console.log(err);
  }
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

const handleSortChange = (event) => {
  sortBy.value = event;
  fetchMachines();
};

const handlePageChange = (page) => {
  pagination.value.current_page = page;
  fetchMachines();
};

const debouncedFetchData = debounce(fetchMachines, 500);

watch([search, selectedMaker, shop, line], () => {
  pagination.value.current_page = 1; // Reset to page 1 when filters change
  debouncedFetchData();
});

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedMachine.value = props.items;

      // Reset pagination to first page when dialog opens
      pagination.value.current_page = 1;

      fetchMachines();
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
            :rules="[requiredValidator]"
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

      <div class="table-container v-table-row-odd-even">
        <VTable fixed-header class="text-no-wrap" height="560">
          <thead>
            <tr>
              <th
                @click="
                  handleSortChange({
                    key: 'machinename',
                    order:
                      sortBy.key === 'machinename'
                        ? sortBy.order === 'asc'
                          ? 'desc'
                          : 'asc'
                        : 'asc',
                  })
                "
              >
                Machine
                <VIcon
                  v-if="sortBy.key === 'machinename'"
                  :icon="
                    sortBy.order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
                  "
                  small
                />
              </th>
              <th
                @click="
                  handleSortChange({
                    key: 'plantcode',
                    order:
                      sortBy.key === 'plantcode'
                        ? sortBy.order === 'asc'
                          ? 'desc'
                          : 'asc'
                        : 'asc',
                  })
                "
              >
                Plant
                <VIcon
                  v-if="sortBy.key === 'plantcode'"
                  :icon="
                    sortBy.order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
                  "
                  small
                />
              </th>
              <th
                @click="
                  handleSortChange({
                    key: 'linecode',
                    order:
                      sortBy.key === 'linecode'
                        ? sortBy.order === 'asc'
                          ? 'desc'
                          : 'asc'
                        : 'asc',
                  })
                "
              >
                Line
                <VIcon
                  v-if="sortBy.key === 'linecode'"
                  :icon="
                    sortBy.order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
                  "
                  small
                />
              </th>
              <th
                @click="
                  handleSortChange({
                    key: 'modelname',
                    order:
                      sortBy.key === 'modelname'
                        ? sortBy.order === 'asc'
                          ? 'desc'
                          : 'asc'
                        : 'asc',
                  })
                "
              >
                Model
                <VIcon
                  v-if="sortBy.key === 'modelname'"
                  :icon="
                    sortBy.order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
                  "
                  small
                />
              </th>
              <th
                @click="
                  handleSortChange({
                    key: 'makername',
                    order:
                      sortBy.key === 'makername'
                        ? sortBy.order === 'asc'
                          ? 'desc'
                          : 'asc'
                        : 'asc',
                  })
                "
              >
                Maker
                <VIcon
                  v-if="sortBy.key === 'makername'"
                  :icon="
                    sortBy.order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
                  "
                  small
                />
              </th>
              <th
                @click="
                  handleSortChange({
                    key: 'shopname',
                    order:
                      sortBy.key === 'shopname'
                        ? sortBy.order === 'asc'
                          ? 'desc'
                          : 'asc'
                        : 'asc',
                  })
                "
              >
                Shop
                <VIcon
                  v-if="sortBy.key === 'shopname'"
                  :icon="
                    sortBy.order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
                  "
                  small
                />
              </th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in data" :key="item.machino">
              <td>
                <div class="d-flex flex-column">
                  <span style="font-weight: 500">{{ item.machinename }}</span>
                  <small>{{ item.machineno }}</small>
                </div>
              </td>
              <td>
                {{ item.plantcode }}
              </td>
              <td>
                {{ item.linecode }}
              </td>
              <td>
                {{ item.modelname }}
              </td>
              <td>
                <div class="d-flex flex-column">
                  <span style="font-weight: 500">{{ item.makername }}</span>
                  <small>{{ item.makercode }}</small>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column">
                  <span style="font-weight: 500">{{ item.shopname }}</span>
                  <small>{{ item.shopcode }}</small>
                </div>
              </td>

              <td>
                <VCol cols="11" md="1">
                  <a
                    @click.prevent="handleItemClick(item)"
                    style="cursor: pointer"
                    >Select</a
                  >
                </VCol>
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-center mt-4">
        <VPagination
          v-model="pagination.current_page"
          :length="pagination.last_page"
          :total-visible="5"
          @update:model-value="handlePageChange"
        />
      </div>

      <!-- <div class="d-flex justify-end mt-2">
        <VSelect
          v-model="pagination.per_page"
          :items="[5, 10, 15, 20, 25, 50]"
          label="Items per page"
          style="width: 150px"
          @update:model-value="fetchMachines"
        />
      </div> -->
    </VCard>
  </VDialog>
</template>
