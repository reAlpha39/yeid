<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const isSelectInventoryVendorDialogVisible = ref(false);
const isSelectMachineDialogVisible = ref(false);
const isDeleteSelectedMachineDialogVisible = ref(false);

const toast = useToast();
const router = useRouter();
const route = useRoute();

const form = ref();
const usedPartSwitch = ref("Active");
const orderSwitch = ref("Active");

const partCodeTF = ref();
const partNameTF = ref();
const specificationTF = ref();
const brandTF = ref();
const categoryTF = ref();
const barcodeTF = ref();
const addressTF = ref();

const vendorTF = ref();

const unitPriceTF = ref();
const currencyTF = ref();
const orderPartCodeTF = ref();
const initialStockTF = ref();
const noteTF = ref();
const minStockTF = ref();
const minOrderTF = ref();

const machines = ref([]);
const indexMachineDelete = ref();

// previous data for edit
const prevData = ref();
const isEdit = ref(false);

const categories = ["Machine", "Facility", "Jig", "Other"];
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];

const handleVendorSelected = (item) => {
  vendorTF.value = item.VENDORCODE + " | " + item.VENDORNAME;
};

function handleMachinesSelected(items) {
  // if (machines.value.length === 0) {
  //   machines.value = items;
  // } else {
  //   machines.value.push(...items);
  // }

  machines.value = items;
}

function openDeleteDialog(index) {
  isDeleteSelectedMachineDialogVisible.value = true;
  indexMachineDelete.value = index;
}

function deleteSelectedMachine() {
  machines.value.splice(indexMachineDelete.value, 1);
  indexMachineDelete.value = undefined;
  isDeleteSelectedMachineDialogVisible.value = false;
}

async function addMasterPart() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    var machineData = [];

    for (const e of machines.value) {
      machineData.push({
        machine_no: e.MACHINENO,
      });
    }

    console.log(machineData);

    const result = await $api("/master/add-part", {
      method: "POST",
      body: {
        part_code: partCodeTF.value,
        part_name: partNameTF.value,
        category: convertCategory(categoryTF.value),
        specification: specificationTF.value,
        ean_code: barcodeTF.value,
        brand: brandTF.value,
        used_flag: convertSwitch(usedPartSwitch.value),
        address: addressTF.value,
        vendor_code: vendorTF.value.split(" | ")[0],
        unit_price: unitPriceTF.value,
        currency: currencyTF.value,
        min_stock: minStockTF.value,
        min_order: minOrderTF.value,
        note: noteTF.value,
        last_stock_number: initialStockTF.value,
        order_part_code: orderPartCodeTF.value,
        no_order_flag: convertSwitch(orderSwitch.value),
        machines: machineData,
      },

      onResponseError({ response }) {
        // toast.error("Failed to save data");
        errors.value = response._data.errors;
      },
    });

    toast.success("Save part success");
    await router.push("/master/part");
  } catch (err) {
    toast.error("Failed to save part");
    console.log(err);
  }
}

async function fetchDataEdit(partCode) {
  try {
    const response = await $api("/master/part-list", {
      params: {
        part_code: partCode,
        max_rows: 1,
      },
    });
    console.log(response.data);
    prevData.value = response.data[0];
  } catch (err) {
    console.log(err);
  }
}

async function fetchVendor(vendorCode) {
  try {
    let response = await $api("/getVendor", {
      params: {
        query: vendorCode,
      },
    });

    var vendor = response.data[0];

    vendorTF.value = vendor.VENDORCODE + " | " + vendor.VENDORNAME;
  } catch (err) {
    console.log(err);
  }
}

async function getMachines(partCode) {
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

    console.log(result.data);

    // machines.value.push(result["data"]);
    machines.value = result.data;
  } catch (err) {
    console.log(err);
  }
}

function convertCategory(category) {
  console.log("selected category: " + category);
  switch (category) {
    case "Machine":
      return "M";
    case "Facility":
      return "F";
    case "Jig":
      return "J";
    case "Other":
      return "O";
    default:
      return "-";
  }
}

function categoryType(category) {
  switch (category) {
    case "M":
      return "Machine";
    case "F":
      return "Facility";
    case "J":
      return "Jig";
    case "O":
      return "Other";
    default:
      return "-";
  }
}

function convertSwitch(val) {
  if (val === "Active") {
    return true;
  } else {
    return false;
  }
}

async function initEditData(partCode) {
  await fetchDataEdit(partCode);
  applyData();
  await fetchVendor(vendorTF.value);
  await getMachines(partCodeTF.value);
}

function applyData() {
  const data = prevData.value;
  usedPartSwitch.value = data.USEDFLAG == "O" ? "Active" : "Inactive";
  orderSwitch.value = data.NOORDERFLAG == "1" ? "Active" : "Inactive";
  partCodeTF.value = data.PARTCODE;
  partNameTF.value = data.PARTNAME;
  specificationTF.value = data.SPECIFICATION;
  brandTF.value = data.BRAND;
  categoryTF.value = categoryType(data.CATEGORY);
  barcodeTF.value = data.EANCODE;
  addressTF.value = data.ADDRESS;
  vendorTF.value = data.VENDORCODE;
  unitPriceTF.value = data.UNITPRICE;
  currencyTF.value = data.CURRENCY;
  orderPartCodeTF.value = data.ORDERPARTCODE;
  initialStockTF.value = data.LASTSTOCKNUMBER;
  noteTF.value = data.NOTE;
  minStockTF.value = data.MINSTOCK;
  minOrderTF.value = data.MINORDER;
}

onMounted(() => {
  const partCode = route.query.part_code;
  console.log("Fetching data for part_code:", partCode);
  if (partCode) {
    isEdit.value = true;
    initEditData(route.query.part_code);
  }
});
</script>

<template>
  <VForm ref="form" lazy-validation>
    <div>
      <VBreadcrumbs
        class="px-0 pb-2 pt-0"
        :items="[
          {
            title: 'Master',
            class: 'text-h4',
          },
          {
            title: 'Part',
            class: 'text-h4',
          },
          {
            title: 'Add New Part',
            class: 'text-h4',
          },
        ]"
      />
    </div>

    <VCard class="pa-8 mt-4">
      <VRow>
        <VCol cols="6">
          <AppTextField
            v-if="isEdit"
            v-model="partCodeTF"
            :rules="[requiredValidator]"
            label="Part Code"
            placeholder="Input part code"
            readonly
          ></AppTextField>
          <AppTextField
            v-else
            v-model="partCodeTF"
            :rules="[requiredValidator]"
            label="Part Code"
            placeholder="Input part code"
          ></AppTextField>
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="partNameTF"
            :rules="[requiredValidator]"
            label="Part Name"
            placeholder="Input part name"
          ></AppTextField>
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="6">
          <AppTextField
            v-model="specificationTF"
            :rules="[requiredValidator]"
            label="Specification"
            placeholder="Input specification"
          ></AppTextField>
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="brandTF"
            :rules="[requiredValidator]"
            label="Brand"
            placeholder="Input brand"
          ></AppTextField>
        </VCol>
      </VRow>

      <VRow class="align-center">
        <VCol cols="3">
          <AppSelect
            v-model="categoryTF"
            :rules="[requiredValidator]"
            label="Category"
            :items="categories"
            placeholder="Select category"
            append-icon="mdi-chevron-down"
          ></AppSelect>
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="barcodeTF"
            label="Barcode"
            placeholder="Input barcode"
          ></AppTextField>
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="addressTF"
            :rules="[requiredValidator]"
            label="Address"
            placeholder="Input address"
          ></AppTextField>
        </VCol>
        <VCol cols="3">
          <VLabel style="color: #43404f; font-size: 13px">Used Parts</VLabel>
          <VSwitch
            v-model="usedPartSwitch"
            :rules="[requiredValidator]"
            :label="usedPartSwitch"
            false-value="Inactive"
            true-value="Active"
          ></VSwitch>
        </VCol>
      </VRow>

      <AppTextField
        readonly
        class="py-4"
        v-model="vendorTF"
        :rules="[requiredValidator]"
        placeholder="Select vendor"
        label="Vendor"
        @click="
          isSelectInventoryVendorDialogVisible =
            !isSelectInventoryVendorDialogVisible
        "
      >
      </AppTextField>

      <VRow>
        <VCol cols="3">
          <AppTextField
            v-model="unitPriceTF"
            :rules="[requiredValidator]"
            label="Unit Price"
            placeholder="Input unit price"
          ></AppTextField>
        </VCol>
        <VCol cols="3">
          <AppSelect
            v-model="currencyTF"
            :rules="[requiredValidator]"
            label="Currency"
            :items="currencies"
            placeholder="Select currency"
          ></AppSelect>
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="orderPartCodeTF"
            label="Order Part Code"
            placeholder="Input order part code"
          ></AppTextField>
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="initialStockTF"
            label="Initial Stock Number"
            placeholder="Input initial stock number"
          ></AppTextField>
        </VCol>
      </VRow>

      <AppTextField
        v-model="noteTF"
        class="py-4"
        label="Note"
        placeholder="Input note"
      ></AppTextField>

      <VRow>
        <VCol cols="2">
          <AppTextField
            v-model="minStockTF"
            label="Minimum Stock"
            placeholder="0"
          ></AppTextField>
        </VCol>
        <VCol cols="2">
          <AppTextField
            v-model="minOrderTF"
            label="Minimum Order"
            placeholder="0"
          ></AppTextField>
        </VCol>

        <VCol cols="8">
          <VLabel style="color: #43404f; font-size: 13px">No Order</VLabel>
          <VSwitch
            v-model="orderSwitch"
            :label="orderSwitch"
            false-value="Inactive"
            true-value="Active"
          ></VSwitch>
        </VCol>
      </VRow>
    </VCard>

    <VCard class="mt-8 pa-4">
      <VCardTitle>
        <VRow class="d-flex justify-space-between align-center">
          <VCol cols="6">
            <h3 class="mb-0">Machine List</h3>
          </VCol>
          <VCol cols="auto">
            <VBtn
              prepend-icon="tabler-plus"
              @click="
                isSelectMachineDialogVisible = !isSelectMachineDialogVisible
              "
            >
              Add Machine
            </VBtn>
          </VCol>
        </VRow>
      </VCardTitle>

      <VCard flat outlined v-if="machines.length > 0">
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th>Machine Name</th>
              <th>Model Name</th>
              <th>Maker</th>
              <th>Shop Code</th>
              <th>Line</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <!-- First row -->
            <tr v-for="(item, index) in machines" :key="item.MACHINENO">
              <td>
                <div class="d-flex flex-column">
                  <span style="font-weight: 500">{{ item.MACHINENAME }}</span>
                  <small>{{ item.MACHINENO }}</small>
                </div>
              </td>
              <td>
                {{ item.MODELNAME }}
              </td>
              <td>
                {{ item.MAKERNAME }}
              </td>
              <td>
                {{ item.SHOPCODE }}
              </td>
              <td>
                {{ item.LINECODE }}
              </td>
              <td>
                <IconBtn @click="openDeleteDialog(index)">
                  <VIcon icon="tabler-trash" />
                </IconBtn>
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCard>
    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addMasterPart">Save</VBtn>
        <VBtn variant="outlined" color="error" to="/master/part">Cancel</VBtn>
      </VCol>
    </VRow>
  </VForm>

  <SelectInventoryVendor
    v-model:isDialogVisible="isSelectInventoryVendorDialogVisible"
    @submit="handleVendorSelected"
  />

  <SelectMachinesDialog
    v-model:isDialogVisible="isSelectMachineDialogVisible"
    v-model:items="machines"
    @submit="handleMachinesSelected"
  />

  <!-- 👉 Delete Dialog  -->
  <VDialog v-model="isDeleteSelectedMachineDialogVisible" max-width="500px">
    <VCard class="pa-4">
      <VCardTitle class="text-center">
        Are you sure you want to delete this item?
      </VCardTitle>

      <VCardActions class="pt-4">
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="
            isDeleteSelectedMachineDialogVisible =
              !isDeleteSelectedMachineDialogVisible
          "
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deleteSelectedMachine()"
        >
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>
