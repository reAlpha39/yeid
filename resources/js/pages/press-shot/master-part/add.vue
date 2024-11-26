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

const categories = [
  "Process Parts",
  "Maker Standard Parts",
  "Finger",
  "Special Parts",
  "Other",
];
const modelDieData = ref([]);
const machines = ref([]);
const processNames = ref([]);
const userData = ref(null);

const form = ref(null);
const isSelectMachineDialogVisible = ref(false);

// previous data for edit
const prevData = ref();
const isEdit = ref(false);

const selectedMachine = ref(null);
const modelDie = ref(null);
const dieNo = ref(null);
const processName = ref(null);
const partCode = ref(null);
const partName = ref(null);
const specification = ref(null);
const yeidLimit = ref(null);
const makerLimit = ref(null);
const qtyDie = ref(null);
const minStock = ref(null);
const category = ref(null);
const lastExchangeDate = ref(null);
const drawingNo = ref(null);
const autoDetection = ref(null);
const reason = ref(null);

async function submitData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    let body = {
      machine_no: selectedMachine.value.machineno,
      model: modelDie.value.model,
      die_no: modelDie.value.dieno,
      die_unit_no: dieNo.value,
      process_name: processName.value?.processname,
      part_code: partCode.value,
      part_name: partName.value,
      category: convertCategory(category.value),
      company_limit: parseInt(yeidLimit.value),
      maker_limit: parseInt(makerLimit.value),
      auto_flag: autoDetection.value === "Active" ? true : false,
      qtty_per_die: parseInt(qtyDie.value),
      drawing_no: drawingNo.value,
      note: specification.value,
      exchange_datetime: moment(lastExchangeDate.value).format(
        "YYYYMMDDHHmmss"
      ),
      min_stock: parseInt(minStock.value),
      reason: reason.value,
      // TODO: update user code and name based on login
      login_user_code: userData.value?.id,
      login_user_name: userData.value?.name,
    };

    if (isEdit) {
      const response = await $api("/press-shot/master-parts", {
        method: "PUT",
        body: body,
        onResponseError({ response }) {
          toast.error(response._data?.message ?? "Failed to store data");
        },
      });
    } else {
      const response = await $api("/press-shot/master-parts", {
        method: "POST",
        body: body,
        onResponseError({ response }) {
          toast.error(response._data?.message ?? "Failed to store data");
        },
      });
    }

    toast.success("Data saved successfully");
    await router.push("/press-shot/master-part");
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

async function fetchDataProcessName() {
  try {
    const response = await $api("/press-shot/process-names");

    processNames.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataMachine() {
  try {
    const result = await $api("/master/machines/" + route.query.machine_no, {
      method: "GET",
    });

    console.log(result.data);

    // machines.value.push(result["data"]);
    selectedMachine.value = result.data;
  } catch (err) {
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

async function fetchDataEdit() {
  try {
    const response = await $api("/press-shot/master-part", {
      params: {
        part_code: route.query.part_code,
        machine_no: route.query.machine_no,
        process_name: route.query.process_name,
        model: route.query.model,
        die_no: route.query.die_no,
      },
    });
    // console.log(response.data);
    prevData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function initEditData() {
  await fetchDataEdit(partCode);
  applyData();
  await fetchDataMachine(route.query.part_code);
}

function applyData() {
  const data = prevData.value;
  modelDie.value = {
    model: data.model,
    dieno: data.dieno,
    title: data.model + " | " + data.dieno,
  };
  const dateStr = data.exchangedatetime;
  const year = dateStr.substring(0, 4);
  const month = dateStr.substring(4, 6);
  const day = dateStr.substring(6, 8);
  const hour = dateStr.substring(8, 10);
  const minute = dateStr.substring(10, 12);

  dieNo.value = data.dieno;
  processName.value = { processname: data.processname };
  partCode.value = data.partcode;
  partName.value = data.partname;
  specification.value = data.note;
  yeidLimit.value = data.companylimit;
  makerLimit.value = data.makerlimit;
  qtyDie.value = data.qttyperdie;
  minStock.value = data.minstock;
  category.value = categoryType(data.category);
  lastExchangeDate.value = `${year}-${month}-${day} ${hour}:${minute}`;
  drawingNo.value = data.drawingno;
  autoDetection.value = data.autoflag === "1" ? "Active" : "Inactive";
  reason.value = data.reason;
}

function convertCategory(category) {
  // console.log("selected category: " + category);å
  switch (category) {
    case "Process Parts":
      return "P";
    case "Maker Standard Parts":
      return "M";
    case "Finger":
      return "F";
    case "Special Parts":
      return "S";
    case "Other":
      return "O";
    default:
      return "-";
  }
}

function categoryType(category) {
  switch (category) {
    case "P":
      return "Process Parts";
    case "M":
      return "aker Standard Parts";
    case "F":
      return "Finger";
    case "S":
      return "Special Parts";
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

onMounted(async () => {
  const partCode = route.query.part_code;
  if (partCode) {
    isEdit.value = true;
    initEditData();
  }
  fetchUserData();
  fetchDataModelDie();
  fetchDataProcessName();
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
          title: 'Master Part',
          class: 'text-h4',
        },
        {
          title: isEdit ? 'Update Part' : 'Add New Part',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VForm ref="form" @submit.prevent="submitData">
    <VCard class="py-6 px-0 mb-6">
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
          </VCol>
          <VCol cols="auto" class="mr-4">
            <VBtn
              v-if="isEdit === false"
              prepend-icon="tabler-edit"
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

    <VCard v-if="selectedMachine" class="py-6 px-4 mb-6">
      <VRow>
        <VCol cols="6">
          <AppAutocomplete
            v-model="modelDie"
            label="Model"
            placeholder="Select model"
            :rules="[requiredValidator]"
            :items="modelDieData"
            :readonly="isEdit"
            item-title="title"
            return-object
            outlined
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model="dieNo"
            label="Die No."
            :rules="[requiredValidator]"
            :readonly="isEdit"
            placeholder="Input die no"
            outlined
            maxlength="8"
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="processName"
            label="Process Name"
            placeholder="Select process name"
            :rules="[requiredValidator]"
            :items="processNames"
            :readonly="isEdit"
            item-title="processname"
            return-object
            outlined
          />
        </VCol>
      </VRow>
      <VRow>
        <VCol>
          <AppTextField
            v-model="partCode"
            label="Part Code"
            :rules="[requiredValidator]"
            :readonly="isEdit"
            placeholder="Input part code"
            outlined
            maxlength="16"
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model="partName"
            label="Part Name"
            :rules="[requiredValidator]"
            placeholder="Input part name"
            outlined
            maxlength="128"
          />
        </VCol>
        <VCol cols="6">
          <AppTextField
            v-model="specification"
            label="Specification"
            :rules="[requiredValidator]"
            placeholder="Input specification"
            outlined
            maxlength="64"
          />
        </VCol>
      </VRow>
      <VRow>
        <VCol>
          <AppTextField
            v-model.number="yeidLimit"
            label="Yied Limit"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="18"
            @keypress="isNumber($event)"
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model.number="makerLimit"
            label="Maker Limit"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="18"
            @keypress="isNumber($event)"
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model.number="qtyDie"
            label="Qty/Die"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="10"
            @keypress="isNumber($event)"
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model.number="minStock"
            label="Min. Stock"
            :rules="[requiredValidator]"
            placeholder="0"
            outlined
            maxlength="10"
            @keypress="isNumber($event)"
          />
        </VCol>
      </VRow>
      <VRow>
        <VCol>
          <AppAutocomplete
            v-model="category"
            label="Category"
            placeholder="Select category"
            :rules="[requiredValidator]"
            :items="categories"
            outlined
          />
        </VCol>
        <VCol>
          <AppDateTimePicker
            v-model="lastExchangeDate"
            label="Last Exchange Date"
            :rules="[requiredValidator]"
            placeholder="2024-01-01 00:00"
            :config="{
              enableTime: true,
              dateFormat: 'Y-m-d H:i',
              defaultDate: lastExchangeDate,
            }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
        <VCol>
          <AppTextField
            v-model="drawingNo"
            label="Drawing No"
            :rules="[requiredValidator]"
            placeholder="Input drawing no"
            outlined
            maxlength="20"
          />
        </VCol>
        <VCol>
          <VLabel style="color: #43404f; font-size: 13px"
            >Auto Detection</VLabel
          >
          <VSwitch
            v-model="autoDetection"
            :rules="[requiredValidator]"
            :label="autoDetection"
            false-value="Inactive"
            true-value="Active"
          ></VSwitch>
        </VCol>
      </VRow>
      <VRow>
        <VCol>
          <AppTextField
            v-model="reason"
            label="Reason"
            :rules="[requiredValidator]"
            placeholder="Input reason"
            outlined
            maxlength="200"
          />
        </VCol>
      </VRow>
    </VCard>

    <div v-if="selectedMachine" class="d-flex justify-start">
      <VBtn type="submit" color="success" class="mr-4"> Add </VBtn>
      <VBtn variant="outlined" color="error" to="/press-shot/master-part"
        >Cancel</VBtn
      >
    </div>
  </VForm>

  <SelectMachineDialog
    v-model:isDialogVisible="isSelectMachineDialogVisible"
    v-model:items="machines"
    @submit="handleMachinesSelected"
  />
</template>
