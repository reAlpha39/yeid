<script setup>
const searchStaff = ref("");
const data = ref({});
const headers = [
  { title: "Staff Code", key: "employeecode", sortable: true },
  { title: "Staff Name", key: "employeename", sortable: true },
  {
    title: "Action",
    key: "actions",
    sortable: false,
    align: "center",
  },
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

async function fetchData() {
  try {
    const response = await $api("/getStaff", {
      params: {
        query: searchStaff.value,
      },
    });
    data.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

const debouncedFetchData = debounce(fetchData, 500);

watch(searchStaff, () => {
  debouncedFetchData();
});

onMounted(() => {
  fetchData();
});
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 1200"
    @update:model-value="dialogVisibleUpdate"
  >
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Select Staff</h4>
        <p class="text-body-1 text-center mb-6">
          You can only select one staff
        </p>
      </VCardText>

      <AppTextField
        class="pb-4"
        v-model="searchStaff"
        placeholder="Search staff"
        variant="outlined"
      />

      <VDivider />

      <div class="table-container v-table-row-odd-even">
        <VTable fixed-header class="text-no-wrap" height="400">
          <thead>
            <tr>
              <th v-for="header in headers" :key="header.key" class="text-left">
                {{ header.title }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in data" :key="item.employeecode">
              <td>{{ item.employeecode }}</td>
              <td>{{ item.employeename }}</td>
              <td class="text-center">
                <VBtn
                  variant="text"
                  color="primary"
                  @click="handleItemClick(item)"
                >
                  Select
                </VBtn>
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>
  </VDialog>
</template>
