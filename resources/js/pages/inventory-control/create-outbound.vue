<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { VRow } from "vuetify/lib/components/index.mjs";

definePage({
  meta: {
    action: "create",
    subject: "inventoryOutbound",
  },
});

const toast = useToast();
const router = useRouter();

const isSelectInventoryStaffDialogVisible = ref(false);
const isSelectInventoryPartDialogVisible = ref(false);

const selectedStaff = ref({}); // Store the selected item

const machines = ref([]);
const selectedMachine = ref([]);

const parts = ref([]);

const getMachines = async (partCode) => {
  try {
    const result = await $api("/getMachines", {
      method: "GET",
      params: {
        partCode: partCode,
      },

      onResponseError({ response }) {
        toast.error("Failed to save data");
        errors.value = response._data.errors;
      },
    });

    // console.log(result["data"]);

    machines.value.push(result["data"]);
  } catch (err) {
    console.log(err);
  }
};

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
    await router.push("/inventory-control/inventory-outbound");
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

  getMachines(item.partcode);

  selectedMachine.value.push();

  parts.value.push({
    locationid: "P",
    jobcode: "O",
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
    vendorcode: item.vendorcode,
    machineno: "",
    machinename: "",
    note: "",
    employeecode: selectedStaff.value.employeecode,
    // not used on api
    shopName: "",
    lineCode: "",
  });

  // console.log(parts.value);
};

const handleMachineSelected = (index) => {
  parts.value[index].machineno = selectedMachine.value[index].machineno;
  parts.value[index].machinename = selectedMachine.value[index].machinename;
  parts.value[index].shopname = selectedMachine.value[index].shopname;
  parts.value[index].linecode = selectedMachine.value[index].linecode;
  // console.log(parts.value[index]);
};

const handleItemSelected = (item) => {
  selectedStaff.value = item;
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
      <!-- Left side: Staff Title and Subtitle -->
      <div>
        <VCardTitle>Staff</VCardTitle>
        <VCardSubtitle class="text-subtitle-2 text-gray">
          Staff is required, please select an available staff
        </VCardSubtitle>
      </div>
      <!-- Right side: Select Staff Button -->
      <template v-if="selectedStaff.employeecode == null">
        <!-- If a staff is selected, show the staff info -->
        <VBtn
          @click="
            isSelectInventoryStaffDialogVisible =
              !isSelectInventoryStaffDialogVisible
          "
          >Select Staff</VBtn
        >
      </template>
    </div>
    <template v-if="selectedStaff.employeecode">
      <VCard
        flat
        border
        class="pa-4 ma-3"
        style="background-color: #e8776814; border-color: #e87768"
      >
        <div class="d-flex justify-space-between align-center">
          <div class="d-flex flex-column">
            <text
              ><strong> {{ selectedStaff.employeename }}</strong></text
            >
            <small> {{ selectedStaff.employeecode }}</small>
          </div>

          <VBtn
            @click="
              isSelectInventoryStaffDialogVisible =
                !isSelectInventoryStaffDialogVisible
            "
            >Change Staff</VBtn
          >
        </div>
      </VCard>
    </template>
  </VCard>

  <!-- List Part Card -->
  <VCard v-if="selectedStaff.employeecode" class="mb-6 pa-6">
    <VRow class="d-flex pb-8 pt-4 pr-3 justify-space-between">
      <VCardTitle>List Part</VCardTitle>

      <VBtn
        color="primary"
        @click="
          isSelectInventoryPartDialogVisible =
            !isSelectInventoryPartDialogVisible
        "
      >
        Add Part
      </VBtn>
    </VRow>

    <template v-for="(part, index) in parts" :key="index">
      <VCard flat border>
        <VCol>
          <VRow class="pa-4">
            <VCol cols="12" md="11">
              <div class="d-flex flex-column">
                <span
                  class="d-block font-weight-medium text-high-emphasis text-truncate"
                  >{{ part.partname }}</span
                >
                <small>{{ part.partcode }}</small>
              </div>
            </VCol>
            <!-- 👉 Item Actions -->
            <VCol cols="12" md="1" class="flex-column align-end">
              <IconBtn @click="deleteItem(item)">
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </VCol>
          </VRow>

          <VDivider />

          <VRow class="align-center px-2 py-4">
            <VCol cols="12" md="2" sm="4">
              <AppTextField
                v-model.number="part.quantity"
                type="number"
                placeholder="5"
                min="0"
                v-on:input="updateQuantity(index)"
              />
            </VCol>
            <VCol cols="12" md="1" sm="4">
              <text>x</text>
            </VCol>
            <VCol cols="12" md="5" sm="4">
              <p class="my-2">
                <span class="text-high-emphasis">
                  {{ part.currency }}
                  {{ part.unitprice.toLocaleString() }}</span
                >
              </p>
            </VCol>
            <VCol cols="12" md="4" sm="4">
              <p class="my-2 align">
                <span class="text-high-emphasis">
                  Total Price
                  {{ part.currency }}
                  {{ part.price.toLocaleString() }}</span
                >
              </p>
            </VCol>
          </VRow>

          <VRow class="align-center px-2 pb-4">
            <VCol cols="3" class="d-flex align-center justify-center">
              <AppSelect
                v-model="selectedMachine[index]"
                :items="machines[index]"
                item-title="machineno"
                label="Machine"
                placeholder="Select machine"
                @update:modelValue="handleMachineSelected(index)"
                return-object
              />
            </VCol>
            <VCol cols="9">
              <AppTextField
                v-model="part.note"
                label="Note"
                placeholder="Input note"
              />
            </VCol>
          </VRow>

          <VDivider />

          <div style="background-color: #f9f9f9">
            <VRow class="align-center px-2 pt-4">
              <VCol cols="6" class="d-flex align-center">
                <text class="align-left">
                  <span class="text-high-emphasis">
                    Machine No :
                    {{ part.machineno }}
                  </span>
                </text>
              </VCol>
              <VCol cols="6">
                <text class="align">
                  <span class="text-high-emphasis">
                    Specification :
                    {{ part.specification }}
                  </span>
                </text>
              </VCol>
            </VRow>

            <VRow class="align-center px-2">
              <VCol cols="6" class="d-flex align-center">
                <text class="align-left">
                  <span class="text-high-emphasis">
                    Shop & Line :
                    {{ part.shopname }}
                    &
                    {{ part.linecode }}
                  </span>
                </text>
              </VCol>
              <VCol cols="6">
                <text class="align">
                  <span class="text-high-emphasis">
                    Vendor :
                    {{ part.vendorcode }}
                  </span>
                </text>
              </VCol>
            </VRow>

            <VRow class="align-center px-2">
              <VCol cols="6" class="d-flex align-center">
                <text class="align-left">
                  <span class="text-high-emphasis">
                    Brand :
                    {{ part.brand }}
                  </span>
                </text>
              </VCol>
              <VCol cols="6">
                <text class="align">
                  <span class="text-high-emphasis">
                    Note :
                    {{ part.note }}
                  </span>
                </text>
              </VCol>
            </VRow>
          </div>
        </VCol>
      </VCard>
      <br />
    </template>
  </VCard>

  <VRow class="d-flex justify-start">
    <VCol>
      <VBtn color="success" class="me-4" @click="saveInbound()">Save</VBtn>
      <VBtn variant="outlined" color="error" to="inventory-outbound"
        >Cancel</VBtn
      >
    </VCol>
  </VRow>

  <SelectStaffDialog
    v-model:isDialogVisible="isSelectInventoryStaffDialogVisible"
    @submit="handleItemSelected"
  />

  <SelectInventoryPartDialog
    v-model:isDialogVisible="isSelectInventoryPartDialogVisible"
    @submit="handlePartSelected"
  />
</template>
