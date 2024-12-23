<script setup>
import moment from "moment";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "pressShot",
  },
});

const toast = useToast();
const router = useRouter();
const route = useRoute();

const modelDieData = ref([]);
const machineNoData = ref([]);
const machines = ref([]);
const dieUnits = ref([]);
const userData = ref(null);

const now = new Date();
const form = ref(null);

const isSelectMachineDialogVisible = ref(false);
const selectedMachine = ref(null);
const machineNo = ref(null);
const modelDie = ref(null);
const dieUnit = ref(null);
const startDate = ref(formatDateTime(now));
const endDate = ref(formatDateTime(now));
const shotCount = ref("0");
const reason = ref(null);

function formatDateTime(date) {
  return moment(date).format("YYYY-MM-DD HH:mm:ss");
}

async function addProductionData() {
  try {
    let body = {
      machine_no: machineNo.value.machineno,
      model: modelDie.value.model,
      die_no: modelDie.value.dieno,
      die_unit_no: dieUnit.value,
      shot_count: parseInt(shotCount.value),
      start_datetime: moment(startDate.value).format("YYYYMMDDHHmmss"),
      end_datetime: moment(endDate.value).format("YYYYMMDDHHmmss"),
      reason: reason.value,
      // TODO: update user code and name based on login
      login_user_code: userData.value?.id,
      login_user_name: userData.value?.name,
    };

    const response = await $api("/press-shot/productions", {
      method: "POST",
      body: body,
    });

    toast.success("Data saved successfully");
    await router.push("/press-shot/production-data");
  } catch (err) {
    toast.error("Failed to store data");
    console.log(err);
  }
}

async function fetchUserData() {
  try {
    const response = await $api("/auth/user");

    userData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataModelDie() {
  try {
    const response = await $api("/press-shot/exchange/model-dies", {
      params: {
        machine_no: "",
      },
    });

    modelDieData.value = response.data;

    modelDieData.value.forEach((data) => {
      data.title = data.model + " | " + data.dieno;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataMachineNo() {
  try {
    const response = await $api("/press-shot/exchange/machines-no");

    machineNoData.value = response.data;

    machineNoData.value.forEach((data) => {
      data.title = data.machineno + " | " + data.machinename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataDieUnit() {
  try {
    const response = await $api("/press-shot/exchange/die-units");

    dieUnits.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function submitData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  await addProductionData();
}

const resetForm = () => {
  refVForm.value?.reset();
};

function handleMachinesSelected(items) {
  selectedMachine.value = items;
}

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

onMounted(() => {
  fetchUserData();
  fetchDataModelDie();
  fetchDataMachineNo();
  fetchDataDieUnit();
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Press Shot',
          class: 'text-h4',
        },
        {
          title: 'Production Data',
          class: 'text-h4',
        },
        {
          title: 'Create Production Data',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VForm ref="form" @submit.prevent="submitData">
    <VCard class="py-6 px-4 mb-6">
      <VRow class="d-flex justify-space-between align-center mb-4">
        <VCol cols="6">
          <VCardTitle> Machine </VCardTitle>
          <small class="ml-4"
            >Machine is required, please select one machine</small
          >
        </VCol>
        <VCol class="mr-2" cols="auto">
          <VBtn
            v-if="selectedMachine === null"
            prepend-icon="tabler-plus"
            @click="
              isSelectMachineDialogVisible = !isSelectMachineDialogVisible
            "
          >
            Add Machine
          </VBtn>
        </VCol>
      </VRow>

      <VCard
        v-if="selectedMachine"
        variant="outlined"
        class="py-4 px-4 mb-4 mx-4"
        style="background-color: #e8776814"
      >
        <VRow class="d-flex justify-space-between align-center">
          <VCol cols="6">
            <VRow no-gutters>
              <VCol cols="12">
                <VCardTitle>{{
                  selectedMachine?.machinename ?? "-"
                }}</VCardTitle>
              </VCol>
            </VRow>
            <VRow class="ml-4" no-gutters>
              <VCol cols="12">
                <small>{{ selectedMachine?.machineno ?? "-" }}</small>
              </VCol>
            </VRow>
            <VRow class="ml-4" no-gutters>
              <VCol cols="12">
                <small>Model : {{ selectedMachine?.modelname ?? "=" }}</small>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="auto" class="mr-4">
            <VBtn
              prepend-icon="tabler-plus"
              @click="
                isSelectMachineDialogVisible = !isSelectMachineDialogVisible
              "
            >
              Change Machine
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VCard>

    <VCard v-if="selectedMachine" class="py-6 px-4 mb-2">
      <VRow class="mb-2">
        <VCol cols="3">
          <AppDateTimePicker
            v-model="startDate"
            label="Start Date"
            :rules="[requiredValidator]"
            placeholder="2024-01-01 00:00"
            :config="{
              enableTime: true,
              dateFormat: 'Y-m-d H:i',
              time_24hr: true,
            }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
        <VCol cols="3">
          <AppDateTimePicker
            v-model="endDate"
            label="End Date"
            :rules="[requiredValidator]"
            placeholder="2024-01-01 00:00"
            :config="{
              enableTime: true,
              dateFormat: 'Y-m-d H:i',
              time_24hr: true,
            }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
      </VRow>
      <VRow class="mb-2">
        <VCol>
          <AppAutocomplete
            v-model="machineNo"
            label="Machine No"
            placeholder="Select machine no"
            :rules="[requiredValidator]"
            :items="machineNoData"
            item-title="title"
            return-object
            outlined
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="modelDie"
            label="Model"
            placeholder="Select model"
            :rules="[requiredValidator]"
            :items="modelDieData"
            item-title="title"
            return-object
            outlined
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="dieUnit"
            label="Die Unit"
            placeholder="Select die unit"
            :rules="[requiredValidator]"
            :items="dieUnits"
            return-object
            outlined
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model.number="shotCount"
            label="Count Shot"
            :rules="[requiredValidator]"
            placeholder="Input count shot"
            outlined
            maxlength="12"
            @keypress="isNumber($event)"
          />
        </VCol>
      </VRow>

      <AppTextField
        v-model="reason"
        label="Reason"
        :rules="[requiredValidator]"
        placeholder="Input reason"
        outlined
        maxlength="50"
      />
    </VCard>

    <VCol v-if="selectedMachine">
      <div class="d-flex justify-start">
        <VBtn type="submit" color="success" class="mr-4"> Add </VBtn>
        <VBtn variant="outlined" color="error" to="/press-shot/production-data"
          >Cancel</VBtn
        >
      </div>
    </VCol>
  </VForm>

  <SelectMachineDialog
    v-model:isDialogVisible="isSelectMachineDialogVisible"
    v-model:items="machines"
    @submit="handleMachinesSelected"
  />
</template>
