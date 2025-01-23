<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "invControlInbound",
  },
});

const toast = useToast();
const router = useRouter();

const isSelectInventoryVendorDialogVisible = ref(false);
const isSelectInventoryPartDialogVisible = ref(false);
const selectedVendor = ref({}); // Store the selected item
const parts = ref([]);

const saveInbound = async () => {
  try {
    const result = await $api("/storeInvRecord", {
      method: "POST",
      body: {
        records: parts.value,
      },

      onResponseError({ response }) {
        toast.error("Failed to save data");
        errors.value = response._data.errors;
      },
    });

    // console.log(result);
    toast.success("Save inbound success");
    await router.push("/inventory-control/inventory-inbound");
  } catch (err) {
    console.log(err);
  }
};

const handlePartSelected = (item) => {
  // showToast('primary','top-right')
  const now = new Date();

  // Function to format date as 'YYYYMMDD'
  const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}${month}${day}`;
  };

  // Function to format time as 'HHMMSS'
  const formatTime = (date) => {
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    const seconds = String(date.getSeconds()).padStart(2, "0");

    return `${hours}${minutes}${seconds}`;
  };

  parts.value.push({
    locationid: "P",
    jobcode: "I",
    jobdate: formatDate(now), // Format date as 'YYYYMMDD'
    jobtime: formatTime(now), // Format time as 'HHMMSS'
    partcode: item.partcode,
    partname: item.partname,
    specification: item.specification,
    brand: item.brand,
    usedflag: "",
    quantity: 1,
    unitprice: item.unitprice,
    price: item.unitprice,
    currency: item.currency,
    vendorcode: selectedVendor.value.vendorcode,
    machineno: "",
    machinename: "",
    note: "",
    employeecode: "",
  });
};

const handleItemSelected = (item) => {
  selectedVendor.value = item;
};

const calculateTotalPrice = (part) => {
  return part.quantity * part.unitprice;
};

const updateQuantity = (index) => {
  let qty = parts.value[index].quantity;
  qty = String(qty).replace(/[^\d]/g, "");

  qty = parseInt(qty);

  if (isNaN(qty) || qty < 0) {
    qty = 0;
  }

  parts.value[index].quantity = qty;
  parts.value[index].price = calculateTotalPrice(parts.value[index]);
};

const deleteItem = (index) => {
  parts.value.splice(index, 1);
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

  <!-- Vendor Card -->
  <VCard class="mb-6 pa-6">
    <div class="d-flex justify-space-between align-center">
      <!-- Left side: Vendor Title and Subtitle -->
      <div>
        <VCardTitle>Vendor</VCardTitle>
        <VCardSubtitle class="text-subtitle-2 text-gray">
          The vendor is required, please select an available vendor
        </VCardSubtitle>
        <template v-if="selectedVendor.vendorcode">
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
      <template v-if="selectedVendor.vendorcode">
        <!-- If a vendor is selected, show the vendor info -->
        <div>
          <p><strong>Vendor Name:</strong> {{ selectedVendor.vendorname }}</p>
          <p><strong>Vendor Code:</strong> {{ selectedVendor.vendorcode }}</p>
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
  </VCard>

  <!-- List Part Card -->
  <VCard v-if="selectedVendor.vendorcode" class="mb-6 pa-6">
    <VCardTitle>List Part</VCardTitle>

    <!-- eslint-disable vue/no-mutating-props -->
    <VCard
      flat
      class="d-flex flex-sm-row flex-column-reverse"
      style="background-color: #4b3e6414"
    >
      <div class="pa-6 flex-grow-1">
        <VRow class="me-10">
          <VCol cols="12" md="2">
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
          <VCol cols="12" md="2">
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

        <VCol class="flex-grow-1 align-center no-gutters">
          <VRow class="pa-4">
            <VCol cols="12" md="2">
              <div class="d-flex flex-column">
                <span
                  class="d-block font-weight-medium text-high-emphasis text-truncate"
                  >{{ part.partname }}</span
                >
                <small>{{ part.partcode }}</small>
              </div>
            </VCol>
            <VCol cols="12" md="2" sm="4">
              <p class="my-2">{{ part.brand }}</p>
            </VCol>
            <VCol cols="12" md="2">
              <p class="my-2">{{ part.specification }}</p>
            </VCol>
            <VCol cols="12" md="2">
              <p class="my-2">
                {{ part.currency }}
                {{ part.unitprice.toLocaleString() }}
              </p>
            </VCol>
            <VCol cols="12" md="2" sm="4">
              <AppTextField
                v-model.number="part.quantity"
                type="number"
                placeholder="5"
                min="0"
                v-on:input="updateQuantity(index)"
                maxlength="8"
              />
            </VCol>
            <VCol cols="12" md="2" sm="4">
              <p class="my-2">
                <span class="d-inline d-md-none">Price: </span>
                <span class="text-high-emphasis">
                  {{ part.currency }}
                  {{ part.price.toLocaleString() }}</span
                >
              </p>
            </VCol>
          </VRow>

          <VDivider />

          <VRow class="align-center px-2 pt-4">
            <VCol cols="1" class="d-flex align-center justify-center">
              <p class="mb-0">Note</p>
            </VCol>
            <VCol cols="11">
              <VTextField
                v-model="part.note"
                placeholder="Input note"
                hide-details
                dense
                maxlength="128"
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
        isSelectInventoryPartDialogVisible = !isSelectInventoryPartDialogVisible
      "
    >
      Add Part
    </VBtn>
  </VCard>

  <VRow class="d-flex justify-start">
    <VCol>
      <VBtn color="success" class="me-4" @click="saveInbound()">Save</VBtn>
      <VBtn variant="outlined" color="error" to="inventory-inbound"
        >Cancel</VBtn
      >
    </VCol>
  </VRow>

  <SelectInventoryVendor
    v-model:isDialogVisible="isSelectInventoryVendorDialogVisible"
    @submit="handleItemSelected"
  />

  <SelectInventoryPartDialog
    v-model:isDialogVisible="isSelectInventoryPartDialogVisible"
    @submit="handlePartSelected"
  />
</template>
