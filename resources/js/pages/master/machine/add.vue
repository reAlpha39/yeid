<script setup>
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "masterData",
  },
});

const toast = useToast();
const router = useRouter();
const route = useRoute();

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

const prevData = ref();
const isEdit = ref(false);
const isLoadingEditData = ref(false);

async function addData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  const now = new Date();

  try {
    if (isEdit.value) {
      const response = await $api(
        "/master/machines/" + encodeURIComponent(machineNo.value),
        {
          method: "PUT",
          body: {
            machinename: machineName.value,
            plantcode: plantNo.value,
            shopcode: selectedShop.value.shopcode,
            shopname: selectedShop.value.shopname,
            linecode: selectedLine.value,
            modelname: modelName.value,
            makercode: selectedMaker.value.makercode,
            makername: selectedMaker.value.makername,
            serialno: serialNo.value,
            currency: currency.value,
            machineprice: price.value,
            purchaseroot: purchaseRoot.value,
            installdate: installDate.value,
            note: note.value,
            status: convertStatus(status.value),
            rank: rank.value,
            updatetime: now,
          },
          onResponseError({ response }) {
            toast.error(
              response._data.message ?? "Failed to save machine data"
            );
          },
        }
      );

      toast.success("Edit machine success");
    } else {
      const response = await $api("/master/machines", {
        method: "POST",
        body: {
          machineno: machineNo.value,
          machinename: machineName.value,
          plantcode: plantNo.value,
          shopcode: selectedShop.value.shopcode,
          shopname: selectedShop.value.shopname,
          linecode: selectedLine.value,
          modelname: modelName.value,
          makercode: selectedMaker.value.makercode,
          makername: selectedMaker.value.makername,
          serialno: serialNo.value,
          currency: currency.value,
          machineprice: price.value,
          purchaseroot: purchaseRoot.value,
          installdate: installDate.value,
          note: note.value,
          status: convertStatus(status.value),
          rank: rank.value,
          updatetime: now,
        },
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to save machine data");
        },
      });

      toast.success("Save machine success");
    }
    await router.push("/master/machine");
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataEdit(id) {
  try {
    const response = await $api("/master/machines/" + encodeURIComponent(id), {
      onResponseError({ response }) {
        toast.error(response._data.message ?? "Failed to fetch data");
      },
    });
    // console.log(response.data);
    prevData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataMaker(id) {
  try {
    if (id) {
      const response = await $api("/master/makers/" + encodeURIComponent(id), {
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to fetch maker data");
        },
      });

      selectedMaker.value = response.data;
      selectedMaker.value =
        response.data.makercode + " | " + response.data.makername;
    } else {
      const response = await $api("/master/makers", {
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to fetch maker data");
        },
      });

      makers.value = response.data;

      makers.value.forEach((maker) => {
        maker.title = maker.makercode + " | " + maker.makername;
      });
    }
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataShop(id) {
  try {
    if (id) {
      const response = await $api("/master/shops/" + encodeURIComponent(id), {
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to fetch data");
        },
      });

      selectedShop.value = response.data;
      selectedShop.value.title =
        response.data.shopcode + " | " + response.data.shopname;
    } else {
      const response = await $api("/master/shops", {
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to fetch data");
        },
      });

      shops.value = response.data;

      shops.value.forEach((data) => {
        data.title = data.shopcode + " | " + data.shopname;
      });
    }
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataLine({ id = null, shopcode = null } = {}) {
  try {
    if (id) {
      const response = await $api(
        "/master/lines/" +
          encodeURIComponent(id) +
          "/" +
          encodeURIComponent(shopcode),
        {
          onResponseError({ response }) {
            // toast.error(response._data.message ?? "Failed to fetch data");
          },
        }
      );

      let data = response.data[0];

      selectedLine.value = data;
      selectedLine.value.title = data.linecode + " | " + data.linename;
    } else {
      const response = await $api("/master/lines", {
        params: {
          shop_code: shopcode,
        },
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to fetch data");
        },
      });

      lines.value = response.data;
      lines.value.forEach((data) => {
        data.title = data.linecode + " | " + data.linename;
      });
    }
  } catch (err) {
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

async function initEditData(id) {
  isLoadingEditData.value = true;
  try {
    await fetchDataEdit(id);
    applyData();
  } finally {
    isLoadingEditData.value = false;
  }
  // await fetchVendor(vendorTF.value);
  // await getMachines(partCodeTF.value);
}

async function applyData() {
  const data = prevData.value;

  await fetchDataShop(data.shopcode);
  // await fetchDataLine({ shopcode: data.shopcode });
  // await fetchDataLine({ id: data.linecode, shopcode: data.shopcode });
  await fetchDataMaker(data.makercode);

  machineNo.value = data.machineno;
  machineName.value = data.machinename;
  plantNo.value = data.plantcode;
  modelName.value = data.modelname;
  serialNo.value = data.serialno;
  currency.value = data.currency;
  price.value = data.machineprice;
  purchaseRoot.value = data.purchaseroot;
  installDate.value = data.installdate?.trim();
  note.value = data.note;
  status.value = statusType(data.status);
  rank.value = data.rank;
}

// watch(selectedShop, (newValue) => {
//   if (newValue) {
//     selectedLine.value = null;
//     fetchDataLine({ shopcode: newValue.shopcode });
//   }
// });

onMounted(() => {
  // fetchData();
  fetchDataMaker();
  fetchDataShop();
  // fetchDataLine();

  const id = route.query.machine_no;
  console.log("Fetching data for machine_no:", id);
  if (id) {
    isEdit.value = true;
    initEditData(route.query.machine_no);
  }
});
</script>

<template>
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
        title: isEdit ? 'Update Machine' : 'Add New Machine',
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

  <VForm v-else ref="form" lazy-validation>
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
              :disabled="isEdit"
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
            <!-- <AppSelect
              v-model="selectedLine"
              label="Line No"
              :rules="[requiredValidator]"
              placeholder="Select line no"
              item-title="title"
              :items="lines"
              return-object
              outlined
            /> -->
            <AppTextField
              v-model="selectedLine"
              label="Line No"
              placeholder="Input line"
              :rules="[requiredValidator]"
              outlined
              maxlength="2"
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
