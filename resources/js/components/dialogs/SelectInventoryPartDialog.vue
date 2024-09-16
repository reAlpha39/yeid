<script setup>
const data = ref({});
const searchPart = ref("");

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

async function fetchData() {
  try {
    const response = await $api("/getPartInfo", {
      params: {
        query: searchPart.value,
      },
    });

    data.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

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
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Select Part</h4>
        <p class="text-body-1 text-center mb-6">You can only select one part</p>
      </VCardText>

      <VRow class="py-4">
        <VCol md="4">
          <AppTextField
            v-model="searchPart"
            placeholder="Search part"
            variant="outlined"
            v-on:input="fetchData()"
          />
        </VCol>
        <VCol md="4">
          <AppSelect
            v-model="selectedRole"
            placeholder="Select brand"
            :items="roles"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
        <VCol md="4">
          <AppSelect
            v-model="selectedRole"
            placeholder="Select curr"
            :items="roles"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>

      <VDivider />

      <div class="py-3 flex-grow-1">
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

      <template v-for="(item, index) in data" :key="index">
        <div class="py-2">
          <VRow class="me-10">
            <VCol cols="12" md="3">
              <div class="d-flex flex-column align-items-center">
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
    </VCard>
  </VDialog>
</template>
