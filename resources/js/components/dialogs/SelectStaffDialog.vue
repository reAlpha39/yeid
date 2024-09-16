<script setup>
const data = await $api("/getStaff", {
  params: {
    query: "",
  },
});

console.log(data.data);

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
        <h4 class="text-h4 text-center mb-2">Select Staff</h4>
        <p class="text-body-1 text-center mb-6">
          You can only select one staff
        </p>
      </VCardText>

      <div class="pa-2 flex-grow-1">
        <VRow class="me-10 pb-2">
          <VCol cols="11" md="5">
            <h6 class="text-h6">Staff Code</h6>
          </VCol>
          <VCol cols="11" md="5">
            <h6 class="text-h6 ps-2">Staff Name</h6>
          </VCol>
          <VCol cols="11" md="1">
            <h6 class="text-h6">Action</h6>
          </VCol>
        </VRow>
        <VDivider />
      </div>

      <template v-for="(item, index) in data.data" :key="index">
        <div class="pa-2 flex-grow-1">
          <VRow class="me-10">
            <VCol cols="11" md="5">
              <text> {{ item.EMPLOYEECODE }}</text>
            </VCol>
            <VCol cols="11" md="5">
              <text> {{ item.EMPLOYEENAME }}</text>
            </VCol>
            <VCol cols="11" md="1">
              <a @click.prevent="handleItemClick(item)">Select</a>
            </VCol>
          </VRow>
          <VDivider />
        </div>
      </template>
    </VCard>
  </VDialog>
</template>
