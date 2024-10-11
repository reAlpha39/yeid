<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { VCardTitle } from "vuetify/lib/components/index.mjs";

import emptyBoxSvg from '@images/svg/empty-box.svg'

const toast = useToast();
const router = useRouter();
const route = useRoute();

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

const ltfactor = ref([]);
const situations = ref([]);
const factors = ref([]);
const measures = ref([]);
const preventions = ref([]);

const prevData = ref();
const selectedMachine = ref();
const isEdit = ref(false);

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

  await fetchDataMachine(data.MACHINENO);
}

async function fetchLtfactors(id) {
  try {
    const response = await $api("/master/ltfactors", {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    ltfactor.value = response.data;

    ltfactor.value.forEach((data) => {
      data.title = data.LTFACTORCODE + " | " + data.LTFACTORNAME;
    });
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
      data.title = data.SITUATIONCODE + " | " + data.SITUATIONNAME;
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
      data.title = data.FACTORCODE + " | " + data.FACTORNAME;
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
      data.title = data.MEASURECODE + " | " + data.MEASURENAME;
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
      data.title = data.PREVENTIONCODE + " | " + data.PREVENTIONNAME;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
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
  initEditData(route.query.record_id);
  fetchLtfactors();
  fetchSituations();
  fetchFactor();
  fetchMeasure();
  fetchPrevention();
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
        Nomor SPK : {{ prevData?.RECORDID }}
      </VCardTitle>
      <VRow class="px-6 py-4" no-gutters>
        <VCol cols="6">
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Nomor Mesin</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.MACHINENO }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Nama Mesin</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.MACHINENAME }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Model</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ selectedMachine?.MODELNAME }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Shop</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ selectedMachine?.SHOPNAME }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Line</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ selectedMachine?.LINECODE }}</text>
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="6">
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Pemohon</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.ORDEREMPNAME }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="4">
              <text>Mengapa</text>
            </VCol>
            <VCol cols="4">
              <text>: {{ prevData?.ORDERTITLE }}</text>
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
                placeholder="31/01/2024"
                :config="{ dateFormat: 'd/m/Y' }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
            <VCol cols="6">
              <AppDateTimePicker
                v-model="startTime"
                :rules="[requiredValidator]"
                label="Time"
                placeholder="31/01/2024"
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
            v-model="startMinuteStop"
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
                placeholder="31/01/2024"
                :config="{ dateFormat: 'd/m/Y' }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
            <VCol cols="6">
              <AppDateTimePicker
                v-model="finishedTime"
                :rules="[requiredValidator]"
                label="Time"
                placeholder="31/01/2024"
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
            v-model="lineStop"
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
                placeholder="31/01/2024"
                :config="{ dateFormat: 'd/m/Y' }"
                append-inner-icon="tabler-calendar"
              />
            </VCol>
            <VCol cols="6">
              <AppDateTimePicker
                v-model="runProdTime"
                :rules="[requiredValidator]"
                label="Time"
                placeholder="31/01/2024"
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
            v-model="makerManxJam"
            label="Man x Jam (menit)"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="50"
            @keypress="isNumber($event)"
        /></VCol>
        <VCol cols="3"
          ><AppTextField
            v-model="makerServiceFee"
            label="Service Fee (IDR)"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="50"
            @keypress="isNumber($event)"
        /></VCol>
        <VCol cols="3"
          ><AppTextField
            v-model="makerPartPrice"
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
            to="department-request/add"
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
        <VCardText class="my-4 justify-center" style="text-align: center">
          Data pekerjaan maintenance masih kosong. Silakan tambah jadwal
          pekerjaan maintenance.
        </VCardText>
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
            to="department-request/add"
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
        <div class="d-flex justify-center mt-4">
          <VImg
            :src="emptyBoxSvg"
            alt="empty"
            style="width: 120px"
          />
        </div>
        <VCardText class="my-4 justify-center" style="text-align: center">
          Data pekerjaan maintenance masih kosong. Silakan tambah jadwal
          pekerjaan maintenance.
        </VCardText>
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
</template>
