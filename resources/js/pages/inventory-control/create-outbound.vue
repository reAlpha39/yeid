<script setup>
const isSelectInventoryVendorDialogVisible = ref(false);
const selectedVendor = ref({}); // Store the selected item
const parts = ref([
  {
    partName: "MAGNETIC CONTACTOR",
    brand: "MITSUBISHI",
    specification: "S-T 10 200V 1A",
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
  },
  {
    partName: "MAGNETIC CONTACTOR",
    brand: "MITSUBISHI",
    specification: "S-T 10 200V 1A",
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
  },
  {
    partName: "MAGNETIC CONTACTOR",
    brand: "MITSUBISHI",
    specification: "S-T 10 200V 1A",
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
  },
]);

const handleItemSelected = (item) => {
  selectedVendor.value = item;
  console.log("Selected Vendor:", item);
};

const calculateTotalPrice = (part) => {
  return part.qty * part.unitPrice;
};

const updateQuantity = (index, qty) => {
  parts.value[index].qty = qty;
  parts.value[index].totalPrice = calculateTotalPrice(parts.value[index]);
};

const removePart = (index) => {
  parts.value.splice(index, 1);
};
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 inventory-control-create-outbound"
      :items="[
        {
          title: 'Inventory Control',
          class: 'text-h4',
        },
        {
          title: 'Inventory Out-Bound',
          class: 'text-h4',
        },
        {
          title: 'Create Out-Bound',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <!-- Vendor Card -->
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

  <!-- List Part Card -->
  <VCard class="mb-6 pa-6">
    <VCardTitle>List Part</VCardTitle>

    <!-- eslint-disable vue/no-mutating-props -->
    <VCard
      flat
      class="d-flex flex-sm-row flex-column-reverse"
      style="background-color: #4b3e6414"
    >
      <div class="pa-6 flex-grow-1">
        <VRow class="me-10">
          <VCol cols="12" md="3">
            <h6 class="text-h6">Part</h6>
          </VCol>
          <VCol cols="12" md="2">
            <h6 class="text-h6 ps-2">Brand</h6>
          </VCol>
          <VCol cols="12" md="2">
            <h6 class="text-h6 ps-2">Specification</h6>
          </VCol>
          <VCol cols="12" md="2">
            <h6 class="text-h6">Unit Price</h6>
          </VCol>
          <VCol cols="12" md="1">
            <h6 class="text-h6">Qty</h6>
          </VCol>
          <VCol cols="12" md="2">
            <h6 class="text-h6">Total Price</h6>
          </VCol>
        </VRow>
      </div>
    </VCard>

    <br />

    <template v-for="(part, index) in parts" :key="index">
      <VCard flat border class="d-flex flex-sm-row flex-column-reverse">
        <!-- 👉 Left Form -->

        <VCol class="flex-grow-1 align-center">
          <VRow class="pa-4">
            <VCol cols="12" md="3">
              <div class="d-flex flex-column">
                <span
                  class="d-block font-weight-medium text-high-emphasis text-truncate"
                  >{{ part.partName }}</span
                >
                <small>{{ part.partCode }}</small>
              </div>
            </VCol>
            <VCol cols="12" md="2" sm="4">
              <p class="my-2">{{ part.brand }}</p>
            </VCol>
            <VCol cols="12" md="2">
              <p class="my-2">{{ part.specification }}</p>
            </VCol>
            <VCol cols="12" md="2">
              <p class="my-2">IDR {{ part.unitPrice.toLocaleString() }}</p>
            </VCol>
            <VCol cols="12" md="1" sm="4">
              <AppTextField v-model="part.qty" type="number" placeholder="5" />
            </VCol>
            <VCol cols="12" md="2" sm="4">
              <p class="my-2">
                <span class="d-inline d-md-none">Price: </span>
                <span class="text-high-emphasis"
                  >IDR {{ (part.totalPrice * part.qty).toLocaleString() }}</span
                >
              </p>
            </VCol>
          </VRow>

          <VDivider style="width: 100%; margin: 0; padding: 0" />

          <VRow class="align-center px-2 pt-4 pb-1">
            <VCol cols="1" class="d-flex align-center justify-center">
              <p class="mb-0">Note</p>
            </VCol>
            <VCol cols="11">
              <VTextField
                v-model="part.note"
                placeholder="Input note"
                hide-details
                dense
              />
            </VCol>
          </VRow>
        </VCol>

        <!-- 👉 Item Actions -->
        <div
          class="d-flex flex-column align-end item-actions"
          :class="$vuetify.display.smAndUp ? 'border-s' : 'border-b'"
        >
          <IconBtn @click="deleteItem(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </VCard>
      <br />
    </template>

    <VBtn
      color="primary"
      @click="
        parts.value.push({
          partName: '',
          brand: '',
          specification: '',
          unitPrice: 0,
          qty: 1,
          totalPrice: 0,
        })
      "
    >
      Add Part
    </VBtn>
  </VCard>
</template>
