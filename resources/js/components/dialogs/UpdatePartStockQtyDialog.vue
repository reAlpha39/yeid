<script setup>
import { useToast } from "vue-toastification";
import {
  VBtn,
  VCardSubtitle,
  VCardTitle,
  VRow,
} from "vuetify/lib/components/index.mjs";

const toast = useToast();

const qtyTextField = ref(0);
const data = ref({});

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  id: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const updateQuantity = () => {
  let qty = qtyTextField.value;
  qty = String(qty).replace(/[^\d]/g, "");

  qty = parseInt(qty);

  if (isNaN(qty) || qty < 0) {
    qty = 0;
  }

  qtyTextField.value = qty;
  console.log("value qty: " + qtyTextField.value);
};

async function fetchData() {
  try {
    const response = await $api("/master/part-list", {
      params: {
        part_code: props.id,
        max_rows: 1,
      },
    });
    console.log(response.data);
    data.value = response.data[0];
  } catch (err) {
    console.log(err);
  }
}

async function saveUpdatedQty() {
  try {
    const now = new Date();
    const item = data.value;
    const substract = await $api("/storeInvRecord", {
      method: "POST",
      body: {
        records: [
          {
            locationid: "P",
            jobcode: "A",
            jobdate: formatDate(now), // Format date as 'YYYYMMDD'
            jobtime: formatTime(now), // Format time as 'HHMMSS'
            partcode: item.partcode,
            partname: item.partname,
            specification: item.specification,
            brand: item.brand,
            usedflag: item.usedflag,
            quantity: -parseInt(data.value.totalstock),
            unitprice: item.unitprice,
            price: item.unitprice,
            currency: item.currency,
            vendorcode: item.vendorcode,
            machineno: "",
            machinename: "",
            note: "",
            employeecode: "",
          },
        ],
      },

      onResponseError({ response }) {
        toast.error("Failed to update data");
        errors.value = response._data.errors;
      },
    });

    const result = await $api("/storeInvRecord", {
      method: "POST",
      body: {
        records: [
          {
            locationid: "P",
            jobcode: "A",
            jobdate: formatDate(now), // Format date as 'YYYYMMDD'
            jobtime: formatTime(now), // Format time as 'HHMMSS'
            partcode: item.partcode,
            partname: item.partname,
            specification: item.specification,
            brand: item.brand,
            usedflag: item.usedflag,
            quantity: parseInt(qtyTextField.value),
            unitprice: item.unitprice,
            price: item.unitprice,
            currency: item.currency,
            vendorcode: item.vendorcode,
            machineno: "",
            machinename: "",
            note: "",
            employeecode: "",
          },
        ],
      },

      onResponseError({ response }) {
        toast.error("Failed to update data");
        errors.value = response._data.errors;
      },
    });

    console.log(result);
    toast.success("Quantity stock updated");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    console.log(err);
  }
}

// Function to format date as 'YYYYMMDD'
function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}${month}${day}`;
}

// Function to format time as 'HHMMSS'
function formatTime(date) {
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  const seconds = String(date.getSeconds()).padStart(2, "0");

  return `${hours}${minutes}${seconds}`;
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

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      fetchData();
      console.log("Dialog opened with ID:", props.id); // Print the id when dialog opens
    }
  }
);
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 800"
    @update:model-value="dialogVisibleUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Adjustment Stock Quantity</h4>
      </VCardText>

      <VCard flat class="pa-4" style="background-color: #4b3e6414">
        <VCardTitle>
          {{ data.partname }}
        </VCardTitle>
        <VCardSubtitle>
          {{ data.partcode }}
        </VCardSubtitle>
        <br />

        <VCard flat class="pa-4 mx-2 flex-grow-1" v-if="data.partcode">
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Category </VCol>
            <VCol cols="12" md="8"> : {{ categoryType(data.category) }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Brand </VCol>
            <VCol> : {{ data.brand.trim() ? data.brand : "-" }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Specification </VCol>
            <VCol>
              : {{ data.specification.trim() ? data.specification : "-" }}
            </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Used </VCol>
            <VCol> : {{ data.status }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Currency </VCol>
            <VCol> : {{ data.currency }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Unit Price </VCol>
            <VCol>
              : {{ data.unitprice ? data.unitprice.toLocaleString() : "-" }}
            </VCol>
          </VRow>
        </VCard>
        <br />
      </VCard>

      <VCard
        flat
        class="pa-4 my-4"
        style="background-color: #e8776814; opacity: 8"
      >
        <VRow class="d-flex">
          <VCol> Stock Quantity </VCol>
          <VCol>
            <div style="text-align: right">
              <strong>
                {{ data.totalstock ? data.totalstock.toLocaleString() : "0" }}
              </strong>
            </div>
          </VCol>
        </VRow>
      </VCard>

      <VRow class="d-flex">
        <VCol> Adjustment stock to </VCol>
        <VCol cols="12" md="2">
          <AppTextField
            v-model.number="qtyTextField"
            type="number"
            placeholder="5"
            min="0"
            v-on:input="updateQuantity()"
          />
        </VCol>
      </VRow>

      <VRow class="d-flex justify-start">
        <VCol>
          <VBtn class="me-4" @click.prevent="saveUpdatedQty()">Submit</VBtn>
          <VBtn
            variant="tonal"
            color="error"
            @click="$emit('update:isDialogVisible', false)"
            >Cancel</VBtn
          >
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>
