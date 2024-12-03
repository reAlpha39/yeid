<script setup>
const vendorQuery = ref("");
const data = ref(null);
const fullData = ref(null);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
});

async function fetchVendor() {
  try {
    let response = await $api("/orders", {
      method: "POST",
      onResponseError({ response }) {
        toast.error(response._data.message ?? "Failed to fetch data");
      },
    });
    fullData.value = response.data; // Store complete data
    data.value = response.data; // Initial data display
  } catch (err) {
    console.log(err);
  }
}

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const handleItemClick = (item) => {
  // console.log("Selected item:", item);
  emit("update:isDialogVisible", false);
  emit("submit", item);
  vendorQuery.value = "";
};

// Local search
const searchVendors = () => {
  if (!fullData.value) return;

  if (!vendorQuery.value.trim()) {
    data.value = fullData.value; // Show all data when search is empty
    return;
  }

  const searchTerm = vendorQuery.value.toLowerCase();
  data.value = fullData.value.filter(
    (vendor) =>
      vendor.vendorname.toLowerCase().includes(searchTerm) ||
      vendor.vendorcode.toLowerCase().includes(searchTerm)
  );
};

watch(vendorQuery, () => {
  searchVendors();
});

onMounted(() => {
  fetchVendor();
});
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    @update:model-value="dialogVisibleUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Select Vendor</h4>
        <p class="text-body-1 text-center mb-6">
          You can only select one vendor
        </p>
      </VCardText>

      <AppTextField
        class="pb-4"
        v-model="vendorQuery"
        placeholder="Search vendor"
        variant="outlined"
      />

      <VDivider />

      <div class="table-container">
        <VTable class="v-table">
          <thead>
            <tr>
              <th>Vendor</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in data" :key="item.vendorcode">
              <td>
                <div class="d-flex flex-column py-2">
                  <span style="font-weight: 500">{{ item.vendorname }}</span>
                  <text>{{ item.vendorcode }}</text>
                </div>
              </td>

              <td>
                <a
                  @click.prevent="handleItemClick(item)"
                  style="cursor: pointer"
                  >Select</a
                >
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.share-project-dialog {
  .card-list {
    --v-card-list-gap: 1rem;

    // Ensure the table container doesn't cause overflow
    .table-container {
      width: 100%;
      overflow-x: hidden; // Prevent horizontal scrolling
    }

    // Style the table to ensure it fits within its container
    .v-table {
      width: 100%;
      table-layout: fixed; // Ensures table respects column widths
    }

    // Optional: Style for table header and rows
    .v-table th,
    .v-table td {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap; // Prevent text from wrapping to the next line
    }
  }
}
</style>
