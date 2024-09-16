<script setup>
const data = await $api("/getVendor", {
  params: {
    query: "a",
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
        <h4 class="text-h4 text-center mb-2">Select Part</h4>
        <p class="text-body-1 text-center mb-6">You can only select one part</p>
      </VCardText>

      <div class="pa-2 flex-grow-1">
        <VRow class="me-10 pb-2">
          <VCol cols="12" md="3">
            <h6 class="text-h6">Part</h6>
          </VCol>
          <VCol cols="12" md="2">
            <h6 class="text-h6 ps-2">Brand</h6>
          </VCol>
          <VCol cols="12" md="3">
            <h6 class="text-h6 ps-2">Specification</h6>
          </VCol>
          <VCol cols="12" md="1">
            <h6 class="text-h6">Curr</h6>
          </VCol>
          <VCol cols="12" md="2">
            <h6 class="text-h6">Unit Price</h6>
          </VCol>
          <VCol cols="12" md="1">
            <h6 class="text-h6">Action</h6>
          </VCol>
        </VRow>
        <VDivider />
      </div>

      <template v-for="(item, index) in data.data" :key="index">
        <div class="pa-2 flex-grow-1">
          <VRow class="me-10">
            <VCol cols="12" md="3">
              <div class="d-flex flex-column">
                <span style="font-weight: 500">{{ item.PARTNAME }}</span>
                <small>{{ item.PARTCODE }}</small>
              </div>
            </VCol>
            <VCol cols="12" md="2">
              <text> {{ item.BRAND }}</text>
            </VCol>
            <VCol cols="12" md="3">
              <text>{{ item.SPECIFICATION }}</text>
            </VCol>
            <VCol cols="12" md="1">
              <text> {{ item.CURRENCY }}</text>
            </VCol>
            <VCol cols="12" md="2">
              <text>{{ item.UNITPRICE.toLocaleString() }}</text>
            </VCol>
            <VCol cols="12" md="1">
              <a @click.prevent="handleItemClick(item)">Select</a>
            </VCol>
          </VRow>
          <VDivider />
        </div>
      </template>

      <div class="table-container card-list">
        <VTable class="v-table">
          <thead>
            <tr>
              <th>Part</th>
              <th>Brand</th>
              <th>Specification</th>
              <th>Curr</th>
              <th>Unit Price</th>
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
                {{ item.BRAND }}
              </td>
              <td>
                {{ item.SPECIFICATION }}
              </td>
              <td>
                {{ item.CURRENCY }}
              </td>
              <td>
                {{ item.UNITPRICE }}
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
