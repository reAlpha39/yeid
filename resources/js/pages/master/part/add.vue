<script setup>
import { useToast } from "vue-toastification";

const isSelectInventoryVendorDialogVisible = ref(false);

const toast = useToast();
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
const stockQtyTF = ref();

const machines = ref([]);

const categories = ["Machines", "Facility", "Jig", "Other"];
const currencies = ["USD", "SGD", "JPY", "IDR"];

const handleVendorSelected = (item) => {
  vendorTF.value = item.VENDORCODE + " | " + item.VENDORNAME;
};

onMounted(() => {});
</script>

<template>
  <VForm @submit.prevent="() => {}">
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
            v-model="partCodeTF"
            label="Part Code"
            placeholder="Input part code"
          ></AppTextField>
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="partNameTF"
            label="Part Name"
            placeholder="Input part name"
          ></AppTextField>
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="6">
          <AppTextField
            v-model="specificationTF"
            label="Specification"
            placeholder="Input specification"
          ></AppTextField>
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="brandTF"
            label="Brand"
            placeholder="Input brand"
          ></AppTextField>
        </VCol>
      </VRow>

      <VRow class="align-center">
        <VCol cols="3">
          <AppSelect
            v-model="categoryTF"
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
            label="Address"
            placeholder="Input address"
          ></AppTextField>
        </VCol>
        <VCol cols="3">
          <VLabel style="color: #43404f; font-size: 13px">Used Parts</VLabel>
          <VSwitch
            v-model="usedPartSwitch"
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
            label="Unit Price"
            placeholder="Input unit price"
          ></AppTextField>
        </VCol>
        <VCol cols="3">
          <AppSelect
            v-model="currencyTF"
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
        <VCol cols="2">
          <AppTextField
            v-model="stockQtyTF"
            label="Stock Quantity"
            placeholder="0"
          ></AppTextField>
        </VCol>
        <VCol cols="6">
          <VLabel style="color: #43404f; font-size: 13px">Used Parts</VLabel>
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
            <VBtn prepend-icon="tabler-plus" to="create-inbound">
              Add Machine
            </VBtn>
          </VCol>
        </VRow>
      </VCardTitle>

      <VCard flat outlined>
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
            <tr>
              <td>
                <div>DRAWING 1</div>
                <small>1DR-5D7</small>
              </td>
              <td>5D7</td>
              <td>SIC</td>
              <td>3308</td>
              <td>-</td>
              <td>
                <IconBtn @click="openDeleteDialog(item)">
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
        <VBtn color="success" class="me-4" type="submit">Save</VBtn>
        <VBtn variant="outlined" color="error" type="reset">Cancel</VBtn>
      </VCol>
    </VRow>
  </VForm>

  <SelectInventoryVendor
    v-model:isDialogVisible="isSelectInventoryVendorDialogVisible"
    @submit="handleVendorSelected"
  />
</template>
