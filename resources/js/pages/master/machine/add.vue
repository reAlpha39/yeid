<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);

const statuses = ["Active", "Disposed", "Resting", "Transfered"];
const ranks = ["A", "B", "C"];
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];

const makers = ref([]);
const shops = ref([]);
const lines = ref([]);

const form = ref();

const selectedShop = ref();
const selectedMaker = ref();
const selectedLine = ref();
const machineNo = ref();
const machineName = ref();
const plantNo = ref();
const modelName = ref();
const serialNo = ref();
const currency = ref();
const price = ref();
const purchaseRoot = ref();
const installDate = ref("");
const note = ref();
const status = ref();
const rank = ref();
const installDateMenu = ref(false);

async function addData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  const now = new Date();

  try {
    const response = await $api("/master/machines", {
      method: "POST",
      body: {
        MACHINENO: machineNo.value,
        MACHINENAME: machineName.value,
        PLANTCODE: plantNo.value,
        SHOPCODE: selectedShop.value.SHOPCODE,
        SHOPNAME: selectedShop.value.SHOPNAME,
        LINECODE: selectedLine.value.LINECODE,
        MODELNAME: modelName.value,
        MAKERCODE: selectedMaker.value.MAKERCODE,
        MAKERNAME: selectedMaker.value.MAKERNAME,
        SERIALNO: serialNo.value,
        CURRENCY: currency.value,
        MACHINEPRICE: price.value,
        PURCHASEROOT: purchaseRoot.value,
        INSTALLDATE: installDate.value,
        NOTE: note.value,
        STATUS: convertStatus(status.value),
        RANK: rank.value,
        UPDATETIME: now,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    toast.success("Save machine success");
    await router.push("/master/machine");
  } catch (err) {
    toast.error("Failed to save machine data");
    console.log(err);
  }
}

async function fetchDataMaker() {
  try {
    const response = await $api("/master/makers", {
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    makers.value = response.data;

    makers.value.forEach((maker) => {
      maker.title = maker.MAKERCODE + " | " + maker.MAKERNAME;
    });
  } catch (err) {
    toast.error("Failed to fetch maker data");
    console.log(err);
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops", {
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    shops.value = response.data;

    shops.value.forEach((data) => {
      data.title = data.SHOPCODE + " | " + data.SHOPNAME;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataLine() {
  try {
    const response = await $api("/master/lines", {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    lines.value = response.data;
    lines.value.forEach((data) => {
      data.title = data.LINECODE + " | " + data.LINENAME;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function convertStatus(category) {
  console.log("selected category: " + category);
  switch (category) {
    case "Active":
      return "A";
    case "Disposed":
      return "D";
    case "Resting":
      return "R";
    case "Transfered":
      return "T";
    default:
      return "";
  }
}

function statusType(category) {
  switch (category) {
    case "A":
      return "Active";
    case "D":
      return "Disposed";
    case "R":
      return "Resting";
    case "T":
      return "Transfered";
    default:
      return "";
  }
}

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

onMounted(() => {
  // fetchData();
  fetchDataMaker();
  fetchDataShop();
  fetchDataLine();
});
</script>

<template>
  <VForm ref="form" lazy-validation>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Master',
          class: 'text-h4',
        },
        {
          title: 'Machine',
          class: 'text-h4',
        },
        {
          title: 'Add New Machine',
          class: 'text-h4',
        },
      ]"
    />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="machineNo"
              label="Machine No"
              :rules="[requiredValidator]"
              placeholder="Input machine no"
              outlined
              maxlength="12"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="machineName"
              label="Machine Name"
              :rules="[requiredValidator]"
              placeholder="Input machine name"
              outlined
              maxlength="50"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="plantNo"
              label="Plant No"
              :rules="[requiredValidator]"
              placeholder="Input plant no"
              outlined
              maxlength="1"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="selectedShop"
              label="Shop"
              :rules="[requiredValidator]"
              placeholder="Select shop"
              item-title="title"
              :items="shops"
              return-object
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="selectedLine"
              label="Line No"
              :rules="[requiredValidator]"
              placeholder="Select line no"
              item-title="title"
              :items="lines"
              return-object
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="modelName"
              label="Model"
              placeholder="Input model"
              outlined
              maxlength="50"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="selectedMaker"
              label="Maker"
              :rules="[requiredValidator]"
              placeholder="Select maker"
              item-title="title"
              :items="makers"
              return-object
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="serialNo"
              label="Serial No"
              :rules="[requiredValidator]"
              placeholder="Input serial no"
              outlined
              maxlength="30"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <VRow align="center">
              <VCol cols="12">
                <VLabel style="color: #43404f; font-size: 13px">Price</VLabel>
                <div
                  style="
                    display: flex;
                    align-items: center;
                    border: 1px solid #e0e0e0;
                    border-radius: 6px;
                  "
                >
                  <VSelect
                    v-model="currency"
                    :rules="[requiredValidator]"
                    :items="currencies"
                    variant="plain"
                    style="
                      border-right: 1px solid #e0e0e0;
                      max-width: 80px;
                      padding-bottom: 6px;
                      padding-left: 8px;
                      padding-right: 8px;
                    "
                  ></VSelect>
                  <VTextField
                    v-model.number="price"
                    :rules="[requiredValidator]"
                    placeholder="0"
                    variant="plain"
                    type="number"
                    style="
                      flex: 1;
                      padding-bottom: 6px;
                      padding-left: 8px;
                      padding-right: 8px;
                      border-radius: 0;
                    "
                    maxlength="18"
                    @keypress="isNumber($event)"
                  />
                </div>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="12" sm="3">
            <AppDateTimePicker
              v-model="installDate"
              :rules="[requiredValidator]"
              label="Install Date"
              placeholder="31/01/2024"
              :config="{ dateFormat: 'd/m/Y' }"
              append-inner-icon="tabler-calendar"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="purchaseRoot"
              label="Purchase Root"
              :rules="[requiredValidator]"
              placeholder="Input purchase root"
              outlined
              maxlength="50"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="note"
              label="Note"
              placeholder="Input note"
              outlined
              maxlength="255"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="status"
              label="Status"
              :rules="[requiredValidator]"
              placeholder="Select status"
              :items="statuses"
              outlined
              maxlength="1"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="rank"
              label="Rank"
              placeholder="Select rank"
              :items="ranks"
              outlined
              clearable
              maxlength="1"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addData">Save</VBtn>
        <VBtn variant="outlined" color="error" to="/master/machine"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </VForm>
</template>
