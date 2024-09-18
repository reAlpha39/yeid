<script setup>
import { VCheckbox } from "vuetify/components";

const machineName = ref("");
const modelName = ref("");
const makerName = ref("");
const shopCode = ref("");
const lineCode = ref("");

const selectedMachines = ref([]);

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
    let response = await $api("/master/machine-search", {
      params: {
        machine_name: machineName.value,
        model_name: modelName.value,
        maker_name: makerName.value,
        shop_code: shopCode.value,
        line_code: lineCode.value,
        max_rows: 20,
      },
    });
    // console.log(response.data);
    // selectedMachines.value = [];
    data.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const handleItemClick = () => {
  // Process the item data as needed
  console.log("Selected item:", selectedMachines.value);

  emit("update:isDialogVisible", false);
  emit("submit", selectedMachines.value);
};

// Computed properties to handle the "Select All" and "Indeterminate" states
const selectAll = computed({
  get() {
    return selectedMachines.value.length === data.value.length;
  },
  set(value) {
    if (value) {
      selectedMachines.value = [...data.value]; // Select all
    } else {
      selectedMachines.value = []; // Deselect all
    }
  },
});

const isIndeterminate = computed(() => {
  return (
    selectedMachines.value.length > 0 &&
    selectedMachines.value.length < data.value.length
  );
});

// Function to check if an item is selected
const isSelected = (item) => {
  return selectedMachines.value.includes(item);
};

// Function to toggle the selection of individual items
const toggleItem = (item) => {
  if (isSelected(item)) {
    selectedMachines.value = selectedMachines.value.filter(
      (selectedItem) => selectedItem !== item
    );
  } else {
    selectedMachines.value.push(item);
  }
};

// Function to toggle "Select All"
const toggleSelectAll = () => {
  selectAll.value = !selectAll.value; // Toggles the selectAll state
};

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedMachines.value = props.items;
      console.log("Dialog opened with items:", props.items); // Print the id when dialog opens
    }
  }
);

onMounted(() => {
  fetchMachines();
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

      <VRow>
        <VCol cols="4">
          <AppTextField
            v-model="machineName"
            label="Machine Name"
            placeholder="Input machine mame"
            variant="outlined"
            v-on:input="fetchMachines()"
          />
        </VCol>
        <VCol cols="4">
          <AppTextField
            v-model="modelName"
            label="Model Name"
            placeholder="Input model name"
            variant="outlined"
            v-on:input="fetchMachines()"
          />
        </VCol>
        <VCol cols="4">
          <AppTextField
            v-model="makerName"
            label="Maker"
            placeholder="Input maker"
            variant="outlined"
            v-on:input="fetchMachines()"
          />
        </VCol>
      </VRow>

      <VRow class="pb-4">
        <VCol cols="4">
          <AppTextField
            v-model="shopCode"
            label="Shop Code"
            placeholder="Input shop code"
            variant="outlined"
            v-on:input="fetchMachines()"
          />
        </VCol>
        <VCol cols="4">
          <AppTextField
            v-model="lineCode"
            label="Line"
            placeholder="Input line"
            variant="outlined"
            v-on:input="fetchMachines()"
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
              <th>
                <VCheckbox
                  :model-value="selectAll"
                  label="Select All"
                  :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                ></VCheckbox>
              </th>
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
                <VCheckbox
                  :model-value="isSelected(item)"
                  label="Select"
                  @change="toggleItem(item)"
                ></VCheckbox>
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>

      <VRow class="d-flex justify-start pt-4">
        <VCol>
          <VBtn color="success" class="me-4" @click="handleItemClick"
            >Save</VBtn
          >
          <VBtn variant="outlined" color="error" to="inventory-inbound"
            >Cancel</VBtn
          >
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>
