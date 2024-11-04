<script setup>
const isSelectInventoryPartDialogVisible = ref(false);
const part = ref();
function handlePartSelected(item) {
  part.value = item;
}
</script>

<template>
  <VCard>
    <VRow class="mt-2">
      <VCol>
        <VCardTitle> Part </VCardTitle>
        <text class="ml-4"
          >Select the part to exchange. You can only choose one part.</text
        >
      </VCol>

      <VCol>
        <VRow class="d-flex justify-center py-8 mx-4">
          <VCol class="d-flex justify-end align-center">
            <VBtn @click="$emit('update:isDialogVisible', false)">
              Select Part
            </VBtn>
          </VCol>
        </VRow>
      </VCol>
    </VRow>
  </VCard>

  <SelectInventoryPartDialog
    v-model:isDialogVisible="isSelectInventoryPartDialogVisible"
    @submit="handlePartSelected"
  />
</template>
