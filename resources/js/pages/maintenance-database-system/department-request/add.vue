<script setup>
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();
const route = useRoute();

const isSelectMachineDialogVisible = ref(false);

const jenisPerbaikan = [
  "01|UM",
  "02|BM",
  "03|TBC",
  "04|TBA",
  "05|PvM",
  "06|FM",
  "07|CM",
  "08|CHECH",
  "09|LAYOUT",
];

const pemohons = ref([]);
const shops = ref([]);

const machines = ref([]);

const form = ref();

const selectedJenisPerbaikan = ref();
const orderDate = ref("");
const selectedPemohon = ref();
const selectedShop = ref();
const orderTitle = ref("");
const finishedDate = ref("");
const qty = ref();
const jenisPekerjaanRadio = ref("1");
const selectedMachine = ref();

const prevData = ref();
const isEdit = ref(false);

function handleMachinesSelected(items) {
  selectedMachine.value = items;
}

async function addData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  const now = new Date();

  try {
    // Prepare the data to send in the request
    const requestData = {
      maintenancecode: selectedJenisPerbaikan.value.split("|")[0],
      orderdatetime: orderDate.value,
      orderempcode: selectedPemohon.value.employeecode,
      orderempname: selectedPemohon.value.employeename,
      ordershop: selectedShop.value.shopcode,
      machineno: selectedMachine.value.machineno,
      machinename: selectedMachine.value.machinename,
      ordertitle: orderTitle.value,
      orderfinishdate: finishedDate.value,
      orderjobtype: jenisPekerjaanRadio.value,
      orderqtty: qty.value,
    };

    if (isEdit.value) {
      // Update the existing maintenance record
      const response = await $api(
        "/maintenance-database-system/department-requests/" +
          prevData.value.recordid,
        {
          method: "PUT",
          body: requestData,
          onResponseError({ response }) {
            errors.value = response._data.errors;
          },
        }
      );

      toast.success("Edit department request success");
    } else {
      // Create a new maintenance record
      const response = await $api(
        "/maintenance-database-system/department-requests",
        {
          method: "POST",
          body: requestData,
          onResponseError({ response }) {
            errors.value = response._data.errors;
          },
        }
      );

      toast.success("Save department request success");
    }
    await router.push("/maintenance-database-system/department-request");
  } catch (err) {
    toast.error("Failed to save department request");
    console.log(err);
  }
}

async function fetchDataEdit(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests/" + id
    );
    // console.log(response.data);
    prevData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataShop(id) {
  try {
    if (id) {
      const response = await $api("/master/shops/" + id, {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      selectedShop.value = response.data;
      selectedShop.value.title =
        response.data.shopcode + " | " + response.data.shopname;
    } else {
      const response = await $api("/master/shops", {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      shops.value = response.data;

      shops.value.forEach((data) => {
        data.title = data.shopcode + " | " + data.shopname;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataEmployee(id) {
  try {
    if (id) {
      const response = await $api("/master/employees/" + id, {
        onResponseError({ response }) {
          // errors.value = response._data.errors;
        },
      });

      let data = response.data;

      selectedPemohon.value = data;
      selectedPemohon.value.title = data.employeename;
    } else {
      const response = await $api("/master/employees", {
        onResponseError({ response }) {
          // errors.value = response._data.errors;
        },
      });

      pemohons.value = response.data;
      pemohons.value.forEach((data) => {
        data.title = data.employeename;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataMachine(id) {
  try {
    const response = await $api("/master/machines/" + encodeURIComponent(id), {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    let data = response.data;

    selectedMachine.value = data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function initEditData(id) {
  await fetchDataEdit(id);
  applyData();
}

async function applyData() {
  const data = prevData.value;

  fetchDataShop(data.shopcode);
  fetchDataEmployee(data.orderempcode);
  fetchDataMachine(data.machineno);

  selectedJenisPerbaikan.value = jenisPerbaikan.find((item) =>
    item.startsWith(data.maintenancecode)
  );
  orderDate.value = data.orderdatetime;
  orderTitle.value = data.ordertitle;
  finishedDate.value = data.orderfinishdate;
  qty.value = data.orderqtty;
  jenisPekerjaanRadio.value = data.orderjobtype;
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
  fetchDataShop();
  fetchDataEmployee();

  const id = route.query.record_id;
  console.log("Fetching data for record_id:", id);
  if (id) {
    isEdit.value = true;
    initEditData(route.query.record_id);
  }
});
</script>

<template>
  <VForm ref="form" lazy-validation>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Maintenance Database System',
          class: 'text-h4',
        },
        {
          title: 'Department Request',
          class: 'text-h4',
        },
        {
          title: 'Add Department Request',
          class: 'text-h4',
        },
      ]"
    />

    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" sm="6">
            <AppSelect
              v-model="selectedJenisPerbaikan"
              label="Jenis Perbaikan"
              :rules="[requiredValidator]"
              placeholder="Pilih jenis perbaikan"
              item-title="title"
              :items="jenisPerbaikan"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppDateTimePicker
              v-model="orderDate"
              :rules="[requiredValidator]"
              label="Tanggal Order"
              placeholder="31/01/2024"
              :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }"
              append-inner-icon="tabler-calendar"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" sm="6">
            <AppAutocomplete
              v-model="selectedPemohon"
              label="Pemohon"
              :rules="[requiredValidator]"
              placeholder="Pilih pemohon"
              item-title="title"
              :items="pemohons"
              return-object
              outlined
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppAutocomplete
              v-model="selectedShop"
              label="Shop yang Dituju"
              :rules="[requiredValidator]"
              placeholder="Pilih shop"
              item-title="title"
              :items="shops"
              return-object
              outlined
            />
          </VCol>
        </VRow>

        <AppTextarea
          class="pt-4 pb-6"
          v-model="orderTitle"
          label="Mengapa dan Bagaimana"
          placeholder="Input mengapa dan bagaimana"
          :rules="[requiredValidator]"
          outlined
          maxlength="50"
        />

        <VRow>
          <VCol cols="12" sm="3">
            <AppDateTimePicker
              v-model="finishedDate"
              :rules="[requiredValidator]"
              label="Minta Tanggal Selesai"
              placeholder="31/01/2024"
              :config="{ dateFormat: 'd/m/Y' }"
              append-inner-icon="tabler-calendar"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="qty"
              label="Jumlah"
              :rules="[requiredValidator]"
              placeholder="Input jumlah"
              outlined
              maxlength="12"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol>
            <VLabel style="color: #43404f; font-size: 13px"
              >Jenis Pekerjaan</VLabel
            >
            <VRadioGroup v-model="jenisPekerjaanRadio" inline>
              <VRadio label="Baru" value="1" />
              <VRadio label="Repair" value="2" />
            </VRadioGroup>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard class="mt-6 pa-4">
      <VCardTitle>
        <VRow class="d-flex justify-space-between align-center">
          <VCol cols="6">
            <h3 class="mb-0">Machine</h3>
            <small>Machine is required, please select one machine</small>
          </VCol>
          <VCol cols="auto">
            <VBtn
              prepend-icon="tabler-plus"
              @click="
                isSelectMachineDialogVisible = !isSelectMachineDialogVisible
              "
            >
              {{ selectedMachine ? "Change machine" : "Add machine" }}
            </VBtn>
          </VCol>
        </VRow>
        <VCard class="pa-4 mt-2" variant="outlined" v-if="selectedMachine">
          <h5>{{ selectedMachine.machinename }}</h5>
          <br />
          <VRow>
            <VCol cols="6">
              <VRow>
                <VCol cols="3"> <small> Machine No</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.machineno }}</small>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="3"> <small> Model</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.modelname }}</small>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="3"> <small> Maker</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.makername }}</small>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="3"> <small> Shop</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.shopname }}</small>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="6">
              <VRow>
                <VCol cols="3"> <small> Plant</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.plantcode }}</small>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="3"> <small> Tanggal Instalasi</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.orderDate }}</small>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="3"> <small> Line</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.linecode }}</small>
                </VCol>
              </VRow>
              <VRow>
                <VCol cols="3"> <small> S/N</small> </VCol>
                <VCol cols="3">
                  <small>: {{ selectedMachine.serialno }}</small>
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCard>
      </VCardTitle>
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addData">Save</VBtn>
        <VBtn
          variant="outlined"
          color="error"
          to="/maintenance-database-system/department-request"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </VForm>

  <SelectMachineDialog
    v-model:isDialogVisible="isSelectMachineDialogVisible"
    v-model:items="machines"
    @submit="handleMachinesSelected"
  />
</template>
