<script setup>
const search = ref("");

const selectedMachine = ref();
const selectedMaker = ref();

const makers = ref([]);
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
        shopcode: props.shopcode,
        max_rows: 10,
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
          errors.value = response._data.errors;
        },
      });

      selectedMaker.value = response.data;
      selectedMaker.value =
        response.data.makercode + " | " + response.data.makername;
    } else {
      const response = await $api("/master/makers", {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      makers.value = response.data;

      makers.value.forEach((maker) => {
        maker.title = maker.makercode + " | " + maker.makername;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch maker data");
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

watch(search, () => {
  debouncedFetchData();
});

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedMachine.value = props.items;

      fetchMachines();
      fetchDataMaker();

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
        <VCol cols="6">
          <AppTextField
            v-model="search"
            label="Search"
            placeholder="Search"
            variant="outlined"
          />
        </VCol>

        <VCol cols="6">
          <AppAutocomplete
            v-model="selectedMaker"
            label="Maker"
            :rules="[requiredValidator]"
            placeholder="Select maker"
            item-title="title"
            :items="makers"
            return-object
            outlined
            @update:modelValue="fetchMachines()"
          />
        </VCol>
      </VRow>

      <VDivider />

      <div class="table-container v-table-row-odd-even">
        <VTable fixed-header class="text-no-wrap" height="500">
          <thead>
            <tr>
              <th>Machine Name</th>
              <th>Plant</th>
              <th>Model</th>
              <th>Maker</th>
              <th>Shop Code</th>

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
                {{ item.makername }}
              </td>
              <td>
                {{ item.shopcode }}
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
