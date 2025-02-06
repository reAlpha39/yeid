<script setup>
import { VCheckbox } from "vuetify/components";

const search = ref("");

const selectedEmployees = ref([]);

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

async function fetchEmployees() {
  try {
    let response = await $api("/master/employees", {
      params: {
        search: search.value,
      },
    });
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
  console.log("Selected item:", selectedEmployees.value);

  emit("update:isDialogVisible", false);
  emit("submit", selectedEmployees.value);
};

const selectAll = computed({
  get() {
    return selectedEmployees.value.length === data.value.length;
  },
  set(value) {
    if (value) {
      selectedEmployees.value = [...data.value];
    } else {
      selectedEmployees.value = [];
    }
  },
});

const isIndeterminate = computed(() => {
  return (
    selectedEmployees.value.length > 0 &&
    selectedEmployees.value.length < data.value.length
  );
});

const isSelected = (item) => {
  return selectedEmployees.value.includes(item);
};

const toggleItem = (item) => {
  if (isSelected(item)) {
    selectedEmployees.value = selectedEmployees.value.filter(
      (selectedItem) => selectedItem !== item
    );
  } else {
    selectedEmployees.value.push(item);
  }
};

const toggleSelectAll = () => {
  selectAll.value = !selectAll.value;
};

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      selectedEmployees.value = [...props.items];
    }
  }
);

const debouncedFetchData = debounce(fetchEmployees, 500);

watch(search, () => {
  debouncedFetchData();
});

onMounted(() => {
  fetchEmployees();
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
        <h4 class="text-h4 text-center mb-2">Select Staff</h4>
      </VCardText>

      <AppTextField
        class="mb-4"
        v-model="search"
        placeholder="Search staff"
        variant="outlined"
      />

      <VDivider />

      <div class="table-container v-table-row-odd-even">
        <VTable fixed-header class="text-no-wrap" height="500">
          <thead>
            <tr>
              <th>Staff Code</th>
              <th>Staff Name</th>
              <th class="actions-column">
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
            <tr v-for="item in data" :key="item.machino">
              <td>
                {{ item.employeecode }}
              </td>
              <td>
                {{ item.employeename }}
              </td>
              <td class="actions-column">
                <VCheckbox
                  class="action-buttons"
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

<style scoped>
.actions-column {
  text-align: right;
  width: 170px;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
}
</style>
