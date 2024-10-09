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
});

async function fetchMachines() {
  try {
    console.log(selectedMaker.value);
    let response = await $api("/master/machines", {
      params: {
        search: search.value,
        maker: selectedMaker.value?.MAKERCODE,
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
      const response = await $api("/master/makers/" + id, {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      selectedMaker.value = response.data;
      selectedMaker.value =
        response.data.MAKERCODE + " | " + response.data.MAKERNAME;
    } else {
      const response = await $api("/master/makers", {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      makers.value = response.data;

      makers.value.forEach((maker) => {
        maker.title = maker.MAKERCODE + " | " + maker.MAKERNAME;
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

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedMachine.value = props.items;

      console.log("Dialog opened with items:", props.items);
    }
  }
);

onMounted(() => {
  fetchMachines();
  fetchDataMaker();
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
            v-on:input="fetchMachines()"
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

      <div class="table-container">
        <VTable class="text-no-wrap" height="500">
          <thead>
            <tr>
              <th>Machine Name</th>
              <th>Model Name</th>
              <th>Maker</th>
              <th>Shop Code</th>
              <th>Line</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in data" :key="item.MACHINENO">
              <td>
                <div class="d-flex flex-column">
                  <span style="font-weight: 500">{{ item.MACHINENAME }}</span>
                  <small>{{ item.MACHINENO }}</small>
                </div>
              </td>
              <td>
                {{ item.MODELNAME }}
              </td>
              <td>
                {{ item.MAKERNAME }}
              </td>
              <td>
                {{ item.SHOPCODE }}
              </td>
              <td>
                {{ item.LINECODE }}
              </td>
              <td>
                <VCol cols="11" md="1">
                  <a @click.prevent="handleItemClick(item)">Select</a>
                </VCol>
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>
  </VDialog>
</template>
