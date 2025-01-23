<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "invControlPartList",
  },
});

const isSelectInventoryVendorDialogVisible = ref(false);

const toast = useToast();
const router = useRouter();
const route = useRoute();

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

const requestQuotationDate = ref(null);
const orderDate = ref(null);
const poSendDate = ref(null);
const etdDate = ref(null);

// previous data for edit
const prevData = ref();
const isEdit = ref(false);
const isLoadingEditData = ref(false);

const categories = ["Machine", "Facility", "Jig", "Other"];
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];

function formatDate(date) {
  if (!date) return null;
  // Remove any hyphens and return in YYYYMMDD format
  return date.replace(/-/g, "");
}

async function updateOrder() {
  try {
    const result = await $api("/master/part/ordering", {
      method: "POST",
      body: {
        part_code: partCodeTF.value,
        req_quotation_date: formatDate(requestQuotationDate.value),
        order_date: formatDate(orderDate.value),
        po_sent_date: formatDate(poSendDate.value),
        etd_date: formatDate(etdDate.value),
      },
      headers: {
        Accept: "application/json",
      },
      onResponseError({ response }) {
        toast.error(response._data.message ?? "Failed to update order");
      },
    });

    toast.success(result.message);
    await router.push("/inventory-control/part-list");
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataEdit(partCode) {
  try {
    const response = await $api("/master/part", {
      params: {
        part_code: partCode,
      },
    });
    // console.log(response.data);
    prevData.value = response.data;
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

    vendorTF.value = vendor.vendorcode + " | " + vendor.vendorname;
  } catch (err) {
    console.log(err);
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

async function initEditData(partCode) {
  isLoadingEditData.value = true;
  try {
    await fetchDataEdit(partCode);
    applyData();
    await fetchVendor(vendorTF.value);
  } finally {
    isLoadingEditData.value = false;
  }
}

function convertDate(dateStr) {
  const year = dateStr.substring(0, 4);
  const month = dateStr.substring(4, 6);
  const day = dateStr.substring(6, 8);
  return `${year}-${month}-${day}`;
}

function applyData() {
  const data = prevData.value;
  usedPartSwitch.value = data.usedflag == "O" ? "Active" : "Inactive";
  orderSwitch.value = data.noorderflag == "1" ? "Active" : "Inactive";
  partCodeTF.value = data.partcode;
  partNameTF.value = data.partname;
  specificationTF.value = data.specification;
  brandTF.value = data.brand;
  categoryTF.value = categoryType(data.category);
  barcodeTF.value = data.eancode;
  addressTF.value = data.address;
  vendorTF.value = data.vendorcode;
  unitPriceTF.value = parseInt(data?.unitprice ?? 0);
  currencyTF.value = data.currency;
  orderPartCodeTF.value = data.orderpartcode;
  initialStockTF.value = parseInt(data?.laststocknumber ?? 0);
  noteTF.value = data.note;
  minStockTF.value = parseInt(data?.minstock ?? 0);
  minOrderTF.value = parseInt(data?.minorder ?? 0);
  requestQuotationDate.value = convertDate(data.reqquotationdate);
  orderDate.value = convertDate(data.orderdate);
  poSendDate.value = convertDate(data.posentdate);
  etdDate.value = convertDate(data.etddate);
}

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

onMounted(async () => {
  const partCode = route.query.part_code;
  // console.log("Fetching data for part_code:", partCode);
  if (partCode) {
    isEdit.value = true;
    initEditData(route.query.part_code);
  }
});
</script>

<template>
  <VBreadcrumbs
    class="px-0 pb-2 pt-0"
    :items="[
      {
        title: 'Inventory Control',
        class: 'text-h4',
      },
      {
        title: 'Part',
        class: 'text-h4',
      },
      {
        title: 'Ordering',
        class: 'text-h4',
      },
    ]"
  />

  <div
    v-if="isLoadingEditData"
    class="d-flex flex-column align-center justify-center my-12"
  >
    <VProgressCircular
      indeterminate
      color="primary"
      size="48"
      width="4"
      class="mb-2"
    />
    <VCardText class="text-center text-body-1 text-medium-emphasis">
      Loading data, please wait...
    </VCardText>
  </div>

  <div v-else>
    <VCard class="pa-8 mt-4">
      <VRow>
        <VCol cols="6">
          <AppTextField
            v-model="partCodeTF"
            :rules="[requiredValidator]"
            label="Part Code"
            placeholder="Input part code"
            disabled
          />
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="partNameTF"
            :rules="[requiredValidator]"
            label="Part Name"
            placeholder="Input part name"
            disabled
          />
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="6">
          <AppTextField
            v-model="specificationTF"
            :rules="[requiredValidator]"
            label="Specification"
            placeholder="Input specification"
            disabled
          />
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="brandTF"
            :rules="[requiredValidator]"
            label="Brand"
            placeholder="Input brand"
            disabled
          />
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
            disabled
          />
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="barcodeTF"
            label="Barcode"
            placeholder="Input barcode"
            disabled
          />
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="addressTF"
            :rules="[requiredValidator]"
            label="Address"
            placeholder="Input address"
            maxlength="6"
            disabled
          />
        </VCol>
        <VCol cols="3">
          <VLabel style="color: #43404f; font-size: 13px">Used Parts</VLabel>
          <VSwitch
            v-model="usedPartSwitch"
            :rules="[requiredValidator]"
            :label="usedPartSwitch"
            false-value="Inactive"
            true-value="Active"
            disabled
          />
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
        disabled
      />

      <VRow>
        <VCol cols="3">
          <AppTextField
            v-model="unitPriceTF"
            :rules="[requiredValidator]"
            label="Unit Price"
            placeholder="Input unit price"
            @keypress="isNumber($event)"
            disabled
          />
        </VCol>
        <VCol cols="3">
          <AppSelect
            v-model="currencyTF"
            :rules="[requiredValidator]"
            label="Currency"
            :items="currencies"
            placeholder="Select currency"
            disabled
          />
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="orderPartCodeTF"
            label="Order Part Code"
            placeholder="Input order part code"
            disabled
          />
        </VCol>
        <VCol cols="3">
          <AppTextField
            :disabled="isEdit"
            v-model="initialStockTF"
            label="Initial Stock Number"
            placeholder="Input initial stock number"
            :rules="[requiredValidator]"
            @keypress="isNumber($event)"
            disabled
          />
        </VCol>
      </VRow>

      <AppTextField
        v-model="noteTF"
        class="py-4"
        label="Note"
        placeholder="Input note"
        disabled
      />

      <VRow>
        <VCol cols="2">
          <AppTextField
            v-model="minStockTF"
            label="Minimum Stock"
            placeholder="0"
            :rules="[requiredValidator]"
            @keypress="isNumber($event)"
            disabled
          />
        </VCol>
        <VCol cols="2">
          <AppTextField
            v-model="minOrderTF"
            label="Minimum Order"
            placeholder="0"
            :rules="[requiredValidator]"
            @keypress="isNumber($event)"
            disabled
          />
        </VCol>

        <VCol cols="8">
          <VLabel style="color: #43404f; font-size: 13px">No Order</VLabel>
          <VSwitch
            v-model="orderSwitch"
            :label="orderSwitch"
            false-value="Inactive"
            true-value="Active"
            disabled
          />
        </VCol>
      </VRow>
    </VCard>

    <VCard class="pa-8 mt-4">
      <VRow>
        <VCol>
          <AppDateTimePicker
            v-model="requestQuotationDate"
            :rules="[requiredValidator]"
            label="Request Quotation Date"
            placeholder="31-01-2024"
            :config="{ dateFormat: 'Y-m-d' }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
        <VCol>
          <AppDateTimePicker
            v-model="orderDate"
            :rules="[requiredValidator]"
            label="Order Date"
            placeholder="31-01-2024"
            :config="{ dateFormat: 'Y-m-d' }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
        <VCol>
          <AppDateTimePicker
            v-model="poSendDate"
            :rules="[requiredValidator]"
            label="P/O Send Date"
            placeholder="31-01-2024"
            :config="{ dateFormat: 'Y-m-d' }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
        <VCol>
          <AppDateTimePicker
            v-model="etdDate"
            :rules="[requiredValidator]"
            label="ETD Date"
            placeholder="31-01-2024"
            :config="{ dateFormat: 'Y-m-d' }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
      </VRow>
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="updateOrder">Save</VBtn>
        <VBtn variant="outlined" color="error" to="/inventory-control/part-list"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </div>
</template>
