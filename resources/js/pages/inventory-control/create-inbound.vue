<script setup>
import { VCardSubtitle } from "vuetify/lib/components/index.mjs";

const isSelectInventoryVendorDialogVisible = ref(false);
const selectedVendor = ref({}); // Store the selected item

const handleItemSelected = (item) => {
  selectedVendor.value = item;
  console.log("Selected Vendor:", item);
};
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 inventory-control-create-inbound"
      :items="[
        {
          title: 'Inventory Control',
          class: 'text-h4',
        },
        {
          title: 'Inventory In-Bound',
          class: 'text-h4',
        },
        {
          title: 'Create In-Bound',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VCard class="mb-6 pa-6">
    <div class="d-flex justify-space-between align-center">
      <!-- Left side: Vendor Title and Subtitle -->
      <div>
        <VCardTitle>Vendor</VCardTitle>
        <VCardSubtitle class="text-subtitle-2 text-gray">
          The vendor is required, please select an available vendor
        </VCardSubtitle>
        <template v-if="selectedVendor.PARTNAME">
          <br />
          <VCardSubtitle>
            <a
              @click="
                isSelectInventoryVendorDialogVisible =
                  !isSelectInventoryVendorDialogVisible
              "
            >
              Change Vendor
            </a>
          </VCardSubtitle>
        </template>
      </div>

      <!-- Right side: Select Vendor Button -->
      <template v-if="selectedVendor.PARTNAME">
        <!-- If a vendor is selected, show the vendor info -->
        <div>
          <p><strong>Vendor Name:</strong> {{ selectedVendor.PARTNAME }}</p>
          <p><strong>Vendor Code:</strong> {{ selectedVendor.PARTCODE }}</p>
        </div>
      </template>
      <template v-else>
        <!-- If no vendor is selected, show the button -->
        <VBtn
          @click="
            isSelectInventoryVendorDialogVisible =
              !isSelectInventoryVendorDialogVisible
          "
          >Select Vendor</VBtn
        >
      </template>
    </div>

    <SelectInventoryVendor
      v-model:isDialogVisible="isSelectInventoryVendorDialogVisible"
      @submit="handleItemSelected"
    />
  </VCard>

  <VCard class="mb-6 pa-6">
    <VCardTitle>List Part</VCardTitle>
  </VCard>
</template>
