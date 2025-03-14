<script setup>
const search = ref("");

const selectedMachine = ref();
const selectedMaker = ref();
const shop = ref();
const line = ref();

const makers = ref([]);
const shops = ref([]);
const data = ref([]);

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
        max_rows: 20,
      },
    });
    data.value = response.data;
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

const debouncedFetchData = debounce(fetchMachines, 500);

watch([search, selectedMaker, shop, line], () => {
  debouncedFetchData();
});

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedMachine.value = props.items;

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
        <p class="text-body-1 text-center mb-6">Select Machine</p>
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
        <VTable fixed-header class="text-no-wrap" height="500">
          <thead>
            <tr>
              <th>Machine</th>
              <th>Plant</th>
              <th>Model</th>
              <th>Maker</th>
              <th>Shop</th>
              <th>Line</th>
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
                {{ item.linecode }}
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
    </VCard>
  </VDialog>
</template>
