<script setup>
const data = await $api("/getVendor", {
  params: {
    query: "",
  },
});

// console.log(data.data);

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
  // Process the item data as needed
  console.log("Selected item:", item);

  emit("update:isDialogVisible", false);
  emit("submit", item);
};
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
        <h4 class="text-h4 text-center mb-2">Select Vendor</h4>
        <p class="text-body-1 text-center mb-6">
          You can only select one vendor
        </p>
      </VCardText>

      <div class="table-container">
        <VTable class="text-no-wrap v-table">
          <thead>
            <tr>
              <th>Vendor</th>
              <th>PIC</th>
              <th>Address</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in data.data" :key="item.PARTCODE">
              <td>
                <div class="d-flex flex-column ms-3">
                  <span style="font-weight: 500">{{ item.PARTNAME }}</span>
                  <text>{{ item.PARTCODE }}</text>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column ms-3">
                  <span style="font-weight: 500">{{ item.PARTNAME }}</span>
                  <text>{{ item.PARTCODE }}</text>
                </div>
              </td>
              <td>
                {{ item.ADDRESS }}
              </td>
              <td>
                <a @click.prevent="handleItemClick(item)">Select</a>
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
