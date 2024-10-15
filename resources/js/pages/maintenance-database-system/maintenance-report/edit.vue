<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { VCardTitle } from "vuetify/lib/components/index.mjs";

import AddWorkTimeDialog from "@/components/dialogs/AddWorkTimeDialog.vue";

const toast = useToast();
const router = useRouter();
const route = useRoute();

const form = ref();

const startDate = ref();
const startTime = ref();
const startMinuteStop = ref();

const finishedDate = ref();
const finishedTime = ref();
const lineStop = ref();

const runProdDate = ref();
const runProdTime = ref();
const runProdNextStop = ref();

const makerName = ref();
const makerManxJam = ref();
const makerServiceFee = ref();
const makerPartPrice = ref();

const selectedltfactor = ref();
const ltfactorNote = ref();

const selectedSituation = ref();
const situationNote = ref();

const selectedFactor = ref();
const factorNote = ref();

const selectedMeasure = ref();
const measureNote = ref();

const selectedPrevention = ref();
const preventionNote = ref();

const commentNote = ref();

const totalWorkTime = ref(0);

const ltfactor = ref([]);
const situations = ref([]);
const factors = ref([]);
const measures = ref([]);
const preventions = ref([]);

const prevData = ref();
const selectedMachine = ref();
const isEdit = ref(false);

const isAddWorkTimeDialogVisible = ref(false);
const selectedWorkTime = ref(null);
const addedWorkTime = ref([]);

const isAddChangedPartDialogVisible = ref(false);
const selectedPart = ref(null);
const addedChangedPart = ref([]);

const exchangeRate = ref();

function handleAddedChangedPart(data) {
  let newData = { ...data };

  if (!newData) {
    return;
  }

  if (!newData.partid) {
    newData.partid = addedChangedPart.value.length + 1;
  }

  const existingIndex = addedChangedPart.value.findIndex(
    (item) => item.partid === newData.partid
  );

  if (existingIndex !== -1) {
    addedChangedPart.value[existingIndex] = newData;
  } else {
    addedChangedPart.value.push(newData);
  }

  selectedPart.value = null;
}

function handleDeleteChangedPart(id) {
  // Filter out the item with the matching id
  addedChangedPart.value = addedChangedPart.value.filter(
    (item) => item.partid !== id
  );
}

function handleUpdateChangedPart(id) {
  if (id) {
    const selectedItem = addedChangedPart.value.find(
      (item) => item.partid === id
    );

    if (selectedItem) {
      selectedPart.value = selectedItem;
      isAddChangedPartDialogVisible.value = true;
    } else {
      console.warn(`Item with id ${id} not found.`);
    }
  } else {
    selectedPart.value = null;
    isAddChangedPartDialogVisible.value = true;
  }
}

function handleAddedWorkTime(data) {
  let newData = { ...data };

  if (!newData) {
    return;
  }

  if (!newData.workid) {
    newData.workid = addedWorkTime.value.length + 1;
  }

  const existingIndex = addedWorkTime.value.findIndex(
    (item) => item.workid === newData.workid
  );

  if (existingIndex !== -1) {
    addedWorkTime.value[existingIndex] = newData;
  } else {
    addedWorkTime.value.push(newData);
  }

  selectedWorkTime.value = null;

  calculateTotalWorkTime();

  console.log(addedWorkTime.value);
}

function handleDeleteWorkTime(id) {
  // Filter out the item with the matching id
  addedWorkTime.value = addedWorkTime.value.filter(
    (item) => item.workid !== id
  );

  calculateTotalWorkTime();
}

function handleUpdateWorkTime(id) {
  if (id) {
    const selectedItem = addedWorkTime.value.find((item) => item.workid === id);

    if (selectedItem) {
      selectedWorkTime.value = selectedItem;
      isAddWorkTimeDialogVisible.value = true;
    } else {
      console.warn(`Item with id ${id} not found.`);
    }
  } else {
    selectedWorkTime.value = null;
    isAddWorkTimeDialogVisible.value = true;
  }

  calculateTotalWorkTime();
}

function calculateTotalWorkTime() {
  // Calculate the total work time from addedWorkTime
  totalWorkTime.value = addedWorkTime.value.reduce((total, item) => {
    return (
      total +
      item.inactivetime +
      item.periodicaltime +
      item.questiontime +
      item.preparetime +
      item.checktime +
      item.waittime +
      item.repairtime +
      item.confirmtime
    );
  }, 0);
}

async function fetchDataEdit(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests/" + id
    );
    prevData.value = response.data;
  } catch (err) {
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

  const data = prevData.value;

  await fetchDataMachine(data.machineno);

  if (data.ltfactorcode) {
    await fetchLtfactors(data.ltfactor);
  }
}

async function fetchExchangeRate() {
  try {
    const year = new Date().getFullYear();
    const response = await $api("/master/systems/" + year);

    exchangeRate.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchLtfactors(id) {
  try {
    if (id) {
      const response = await $api("/master/ltfactors" + id);

      selectedltfactor.value = response.data;
      selectedltfactor.value.title =
        response.data.ltfactorcode + response.data.ltfactorname;
    } else {
      const response = await $api("/master/ltfactors");

      ltfactor.value = response.data;

      ltfactor.value.forEach((data) => {
        data.title = data.ltfactorcode + " | " + data.ltfactorname;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchSituations(id) {
  try {
    const response = await $api("/master/situations", {
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    situations.value = response.data;

    situations.value.forEach((data) => {
      data.title = data.situationcode + " | " + data.situationname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchFactor() {
  try {
    const response = await $api("/master/factors", {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    factors.value = response.data;

    factors.value.forEach((data) => {
      data.title = data.factorcode + " | " + data.factorname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchMeasure() {
  try {
    const response = await $api("/master/measures", {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    measures.value = response.data;

    measures.value.forEach((data) => {
      data.title = data.measurecode + " | " + data.measurename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchPrevention() {
  try {
    const response = await $api("/master/preventions", {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    preventions.value = response.data;

    preventions.value.forEach((data) => {
      data.title = data.preventioncode + " | " + data.preventionname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function addData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    const requestData = {
      startdatetime: startDate.value + " " + startTime.value,
      enddatetime: finishedDate.value + " " + finishedTime.value,
      restoreddatetime: runProdDate.value + " " + runProdTime.value,
      machinestoptime: startMinuteStop.value,
      linestoptime: lineStop.value,
      makercode: null,
      yokotenkai: runProdNextStop.value,
      makername: makerName.value,
      makerhour: makerManxJam.value,
      makerservice: makerServiceFee.value,
      makerparts: makerPartPrice.value,
      ltfactorcode: selectedltfactor.value.ltfactorcode,
      ltfactor: ltfactorNote.value,
      situationcode: selectedSituation.value.situationcode,
      situation: situationNote.value,
      factorcode: selectedFactor.value.factorcode,
      factor: factorNote.value,
      measurecode: selectedMeasure.value.measurecode,
      measure: measureNote.value,
      preventioncode: selectedPrevention.value.preventioncode,
      prevention: preventionNote.value,
      comments: commentNote.value,
      staffnum: addedWorkTime.value.length,
      inactivesum: addedWorkTime.value.reduce((total, item) => {
        return total + item.inactivetime;
      }, 0),
      periodicalsum: addedWorkTime.value.reduce((total, item) => {
        return total + item.periodicaltime;
      }, 0),
      questionsum: addedWorkTime.value.reduce((total, item) => {
        return total + item.questiontime;
      }, 0),
      preparesum: addedWorkTime.value.reduce((total, item) => {
        return total + item.preparetime;
      }, 0),
      checksum: addedWorkTime.value.reduce((total, item) => {
        return total + item.checktime;
      }, 0),
      waitsum: addedWorkTime.value.reduce((total, item) => {
        return total + item.waittime;
      }, 0),
      repairsum: addedWorkTime.value.reduce((total, item) => {
        return total + item.repairtime;
      }, 0),
      confirmsum: addedWorkTime.value.reduce((total, item) => {
        return total + item.confirmtime;
      }, 0),
      totalrepairsum: totalWorkTime.value,
      partcostsum: calculateTotalPriceInIDR(),
      workdata: addedWorkTime.value,
      partdata: addedChangedPart.value,
    };

    console.log(requestData);

    const response = await $api(
      "/maintenance-database-system/maintenance-report/" +
        route.query.record_id,
      {
        method: "PUT",
        body: requestData,
      }
    );

    toast.success("Update report success");
    await router.push("/maintenance-database-system/maintenance-report");
  } catch (err) {
    console.log(err);
  }
}

function calculateTotalPriceInIDR() {
  console.log(exchangeRate.value);

  const totalPriceInIDR = addedChangedPart.value.reduce((total, part) => {
    // Get the price, qtty (quantity), and currency of each part
    const { price, qtty, currency } = part;

    // Determine if conversion is needed
    let convertedPrice;
    if (currency === "IDR") {
      convertedPrice = price * qtty;
    } else {
      // Apply conversion rate based on the currency
      let conversionRate = 1;
      if (currency === "USD") {
        conversionRate = exchangeRate.value.usd2idr;
      } else if (currency === "SDG") {
        conversionRate = exchangeRate.value.sgd2idr;
      } else if (currency === "EUR") {
        conversionRate = exchangeRate.value.eur2idr;
      } else if (currency === "JPY") {
        conversionRate = exchangeRate.value.jpy2idr;
      }

      convertedPrice = price * qtty * conversionRate; // Convert to IDR
    }

    // Add the converted price to the total
    return total + convertedPrice;
  }, 0);

  return totalPriceInIDR;
}

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

onMounted(() => {
  initEditData(route.query.record_id);
  fetchLtfactors();
  fetchSituations();
  fetchFactor();
  fetchMeasure();
  fetchPrevention();
  fetchExchangeRate();
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
          title: 'Maintenance Report',
          class: 'text-h4',
        },
        {
          title: 'Update Maintenance Report',
          class: 'text-h4',
        },
      ]"
    />

    <VCard>
      <VCardTitle class="mt-2 mx-2">
        Nomor SPK : {{ prevData?.recordid }}
      </VCardTitle>
      <VRow class="px-6 py-4" no-gutters>
        <VCol cols="6">
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Nomor Mesin</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.machineno }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Nama Mesin</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.machinename }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Model</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ selectedMachine?.modelname }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Shop</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ selectedMachine?.shopname }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Line</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ selectedMachine?.linecode }}</text>
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="6">
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Pemohon</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.orderempname }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Mengapa</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.ordertitle }}</text>
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VCard>

    <br />

    <VRow>
      <VCol cols="4">
        <VCard>
          <VCardTitle class="mt-2 mx-2"> Waktu Mulai Dikerjakan </VCardTitle>
          <VRow class="mx-2">
            <VCol cols="6">
              <AppDateTimePicker
                v-model="startDate"
                :rules="[requiredValidator]"
                label="Tanggal"
                placeholder="2024-01-01"
                :config="{ dateFormat: 'Y-m-d' }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
            <VCol cols="6">
              <AppDateTimePicker
                v-model="startTime"
                :rules="[requiredValidator]"
                label="Time"
                placeholder="12:00"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
          </VRow>
          <br />
          <AppTextField
            class="mx-5"
            v-model.number="startMinuteStop"
            label="Waktu Mesin Stop (menit)"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="12"
            @keypress="isNumber($event)"
          />
          <br />
        </VCard>
      </VCol>
      <VCol cols="4">
        <VCard>
          <VCardTitle class="mt-2 mx-2"> Waktu Selesai Dikerjakan </VCardTitle>
          <VRow class="mx-2">
            <VCol cols="6">
              <AppDateTimePicker
                v-model="finishedDate"
                :rules="[requiredValidator]"
                label="Tanggal"
                placeholder="2024-01-01"
                :config="{ dateFormat: 'Y-m-d' }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
            <VCol cols="6">
              <AppDateTimePicker
                v-model="finishedTime"
                :rules="[requiredValidator]"
                label="Time"
                placeholder="12:00"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
          </VRow>
          <br />
          <AppTextField
            class="mx-5"
            v-model.number="lineStop"
            label="Waktu Line Stop (menit)"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="12"
            @keypress="isNumber($event)"
          />
          <br />
        </VCard>
      </VCol>
      <VCol cols="4">
        <VCard>
          <VCardTitle class="mt-2 mx-2"> Waktu Jalan Produksi </VCardTitle>
          <VRow class="mx-2">
            <VCol cols="6">
              <AppDateTimePicker
                v-model="runProdDate"
                :rules="[requiredValidator]"
                label="Tanggal"
                placeholder="2024-01-01"
                :config="{ dateFormat: 'Y-m-d' }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
            <VCol cols="6">
              <AppDateTimePicker
                v-model="runProdTime"
                :rules="[requiredValidator]"
                label="Time"
                placeholder="12:00"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
          </VRow>
          <br />
          <AppTextField
            class="mx-5"
            v-model="runProdNextStop"
            label="Pengembangan Berikutnya"
            :rules="[requiredValidator]"
            placeholder="Input pengembangan berikutnya"
            outlined
            maxlength="50"
          />
          <br />
        </VCard>
      </VCol>
    </VRow>
    <br />
    <VCard>
      <VCardTitle class="mt-2 mx-2">Maker</VCardTitle>

      <VRow class="mx-3 mt-2">
        <VCol cols="3"
          ><AppTextField
            v-model="makerName"
            label="Nama"
            :rules="[requiredValidator]"
            placeholder="Input nama"
            outlined
            maxlength="50"
        /></VCol>
        <VCol cols="3"
          ><AppTextField
            v-model.number="makerManxJam"
            label="Man x Jam (menit)"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="50"
            @keypress="isNumber($event)"
        /></VCol>
        <VCol cols="3"
          ><AppTextField
            v-model.number="makerServiceFee"
            label="Service Fee (IDR)"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="50"
            @keypress="isNumber($event)"
        /></VCol>
        <VCol cols="3"
          ><AppTextField
            v-model.number="makerPartPrice"
            label="Biaya Parts"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="50"
            @keypress="isNumber($event)"
        /></VCol>
      </VRow>
      <br />
    </VCard>

    <br />

    <VCard>
      <VCardTitle>
        <VRow>
          <VCol cols="4">
            <VCard
              class="my-2"
              variant="flat"
              style="background-color: #f9f9f9"
            >
              <VCardTitle> Stop Panjang </VCardTitle>

              <AppAutocomplete
                style="background-color: #ffffff"
                class="mx-4 mt-4"
                v-model="selectedltfactor"
                :rules="[requiredValidator]"
                placeholder="Select"
                item-title="title"
                :items="ltfactor"
                return-object
                outlined
              />
              <VTextarea
                class="mx-4 my-4"
                style="background-color: #ffffff"
                label="Catatan"
                v-model="ltfactorNote"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard
              class="my-2"
              variant="flat"
              style="background-color: #f9f9f9"
            >
              <VCardTitle> Uraian Masalah </VCardTitle>

              <AppAutocomplete
                style="background-color: #ffffff"
                class="mx-4 mt-4"
                v-model="selectedSituation"
                :rules="[requiredValidator]"
                placeholder="Select"
                item-title="title"
                :items="situations"
                return-object
                outlined
              />
              <VTextarea
                class="mx-4 my-4"
                style="background-color: #ffffff"
                label="Catatan"
                v-model="situationNote"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard
              class="my-2"
              variant="flat"
              style="background-color: #f9f9f9"
            >
              <VCardTitle> Penyebab </VCardTitle>

              <AppAutocomplete
                style="background-color: #ffffff"
                class="mx-4 mt-4"
                v-model="selectedFactor"
                :rules="[requiredValidator]"
                placeholder="Select"
                item-title="title"
                :items="factors"
                return-object
                outlined
              />
              <VTextarea
                class="mx-4 my-4"
                style="background-color: #ffffff"
                label="Catatan"
                v-model="factorNote"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="4">
            <VCard
              class="my-2"
              variant="flat"
              style="background-color: #f9f9f9"
            >
              <VCardTitle> Temporary Tindakan </VCardTitle>

              <AppAutocomplete
                style="background-color: #ffffff"
                class="mx-4 mt-4"
                v-model="selectedMeasure"
                :rules="[requiredValidator]"
                placeholder="Select"
                item-title="title"
                :items="measures"
                return-object
                outlined
              />
              <VTextarea
                class="mx-4 my-4"
                style="background-color: #ffffff"
                label="Catatan"
                v-model="measureNote"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard
              class="my-2"
              variant="flat"
              style="background-color: #f9f9f9"
            >
              <VCardTitle> Solution </VCardTitle>

              <AppAutocomplete
                style="background-color: #ffffff"
                class="mx-4 mt-4"
                v-model="selectedPrevention"
                :rules="[requiredValidator]"
                placeholder="Select"
                item-title="title"
                :items="preventions"
                return-object
                outlined
              />
              <VTextarea
                class="mx-4 my-4"
                style="background-color: #ffffff"
                label="Catatan"
                v-model="preventionNote"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard
              class="my-2"
              variant="flat"
              style="background-color: #f9f9f9"
            >
              <VCardTitle> Komental </VCardTitle>

              <VTextarea
                class="mx-4 my-4"
                style="background-color: #ffffff"
                label="Catatan"
                v-model="commentNote"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCol>
        </VRow>
      </VCardTitle>
    </VCard>

    <br />

    <VCard>
      <VRow>
        <VCol cols="6">
          <VCardTitle class="my-3">Detail untuk waktu kerjakan</VCardTitle>
        </VCol>
        <VCol cols="6" class="d-flex justify-end">
          <VBtn
            class="ma-4"
            prepend-icon="tabler-plus"
            @click="handleUpdateWorkTime()"
          >
            Tambah
          </VBtn>
        </VCol>
      </VRow>

      <VCard
        variant="outlined"
        class="mx-4 px-4 py-2"
        style="background-color: #f9f9f9; width: auto; display: inline-block"
      >
        <text style="text-align: center">
          Total = {{ totalWorkTime }} Menit
        </text>
      </VCard>

      <VCard variant="outlined" class="mx-4">
        <VCardText
          v-if="addedWorkTime.length === 0"
          class="my-4 justify-center"
          style="text-align: center"
        >
          Data pekerjaan maintenance masih kosong. Silakan tambah jadwal
          pekerjaan maintenance.
        </VCardText>
        <div v-else style="overflow-x: auto">
          <VTable class="text-no-wrap" height="250">
            <thead>
              <tr>
                <th>NO</th>
                <th>NAME</th>
                <th>WAKTU<br />SEBELUM</th>
                <th>WAKTU<br />PERIODICAL</th>
                <th>WAKTU<br />PERTANYAAN</th>
                <th>WAKTU<br />SIAPKAN</th>
                <th>WAKTU<br />PENELITIAN</th>
                <th>WAKTU<br />MENUNGGU PART</th>
                <th>WAKTU PEKERJAAN<br />MAINTENANCE</th>
                <th>WAKTU<br />KONFIRMASI</th>
                <th class="actions-column">ACTIONS</th>
                <!-- Added class for Actions column -->
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in addedWorkTime" :key="item.workid">
                <td>{{ item.workid }}</td>
                <td>{{ item.employee.employeename }}</td>
                <td>{{ item.inactivetime }}</td>
                <td>{{ item.periodicaltime }}</td>
                <td>{{ item.questiontime }}</td>
                <td>{{ item.preparetime }}</td>
                <td>{{ item.checktime }}</td>
                <td>{{ item.waittime }}</td>
                <td>{{ item.repairtime }}</td>
                <td>{{ item.confirmtime }}</td>
                <td class="actions-column align-center">
                  <IconBtn>
                    <VIcon
                      @click="handleUpdateWorkTime(item.workid)"
                      icon="tabler-edit"
                    />
                  </IconBtn>
                  <IconBtn>
                    <VIcon
                      @click="handleDeleteWorkTime(item.workid)"
                      icon="tabler-trash"
                    />
                  </IconBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCard>

      <br />
    </VCard>

    <br />

    <VCard>
      <VRow>
        <VCol cols="6">
          <VCardTitle class="my-3">Detail untuk ganti part</VCardTitle>
        </VCol>
        <VCol cols="6" class="d-flex justify-end">
          <VBtn
            class="ma-4"
            prepend-icon="tabler-plus"
            @click="handleUpdateChangedPart()"
          >
            Tambah
          </VBtn>
        </VCol>
      </VRow>

      <VCard
        variant="outlined"
        class="mx-4 px-4 py-2"
        style="background-color: #f9f9f9; width: auto; display: inline-block"
      >
        <text style="text-align: center"> Total = 0 Menit </text>
      </VCard>

      <VCard variant="outlined" class="mx-4">
        <VCardText
          v-if="addedChangedPart.length === 0"
          class="my-4 justify-center"
          style="text-align: center"
        >
          Data parts masih kosong. Silakan tambah parts yang ganti.
        </VCardText>
        <div v-else style="overflow-x: auto">
          <VTable class="text-no-wrap" height="250">
            <thead>
              <tr>
                <th>NO</th>
                <th>PART</th>
                <th>SPESIFIKASI</th>
                <th>BRAND</th>
                <th>QUANTITY</th>
                <th>HARGA</th>
                <th>CURRENCY</th>
                <th class="actions-column">ACTIONS</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in addedChangedPart" :key="item.partid">
                <td>{{ item.partid }}</td>
                <td>
                  {{ item.partname }} <br />
                  <small>{{ item.partcode }}</small>
                </td>
                <td>{{ item.specification }}</td>
                <td>{{ item.brand }}</td>
                <td>{{ item.qtty }}</td>
                <td>{{ item.price }}</td>
                <td>{{ item.currency }}</td>
                <td class="actions-column align-center">
                  <IconBtn>
                    <VIcon
                      @click="handleUpdateChangedPart(item.partid)"
                      icon="tabler-edit"
                    />
                  </IconBtn>
                  <IconBtn>
                    <VIcon
                      @click="handleDeleteChangedPart(item.partid)"
                      icon="tabler-trash"
                    />
                  </IconBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCard>

      <br />
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addData">Save</VBtn>
        <VBtn
          variant="outlined"
          color="error"
          to="/maintenance-database-system/maintenance-report"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </VForm>

  <AddWorkTimeDialog
    v-model:isDialogVisible="isAddWorkTimeDialogVisible"
    v-model:item="selectedWorkTime"
    @submit="handleAddedWorkTime"
  />

  <AddChangePartDialog
    v-model:isDialogVisible="isAddChangedPartDialogVisible"
    v-model:item="selectedPart"
    @submit="handleAddedChangedPart"
  />
</template>

<style scoped>
.actions-column {
  position: sticky;
  right: 0; /* Stick to the right */
  background: white; /* Background color to cover underlying rows */
  z-index: 10; /* Make sure it's above other content */
}

/* Optional: For better visual separation */
tbody tr td {
  padding: 8px;
}

tbody tr:hover {
  background-color: #f5f5f5; /* Optional hover effect */
}
</style>
