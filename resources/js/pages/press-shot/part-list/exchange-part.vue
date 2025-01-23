<script setup>
import moment from "moment";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "pressShotPartList",
  },
});

const toast = useToast();
const router = useRouter();
const route = useRoute();

const isSelectInventoryPartDialogVisible = ref(false);
const part = ref();

const data = ref(null);
const machine = ref(null);
const userData = ref(null);

const form = ref(null);
const now = new Date();

const exchangeDate = ref(formatDateTime(now));
const exchangeQty = ref("0");
const reason = ref(null);

async function initEditData(id) {
  await fetchData(id);
  await fetchDataMachine(data.value?.machineno);
}

async function fetchData(id) {
  try {
    const response = await $api("/press-shot/parts/" + encodeURIComponent(id));

    data.value = response.data;
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

  await addExchangeData();
}

async function addExchangeData() {
  try {
    const response = await $api("/press-shot/exchanges", {
      method: "POST",
      body: {
        exchange_date_time: moment(exchangeDate.value).format("YYYYMMDDHHmmss"),
        machine_no: data.value?.machineno,
        model: data.value?.model,
        die_no: data.value?.dieno,
        process_name: data.value?.processname,
        part_code: data.value?.partcode,
        part_name: data.value?.partname,
        exchange_shot_no: data.value?.counter,
        exchange_qty: parseInt(exchangeQty.value),
        reason: reason.value,
        // TODO: update login user
        login_user_code: userData.value?.id,
        login_user_name: userData.value?.name,
      },
    });

    toast.success("Update exchange data success");
    await router.push("/press-shot/part-list");
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function fetchDataQtyPerDie() {
  exchangeQty.value = data.value?.qttyperdie;
}

async function fetchUserData() {
  try {
    const response = await $api("/auth/user");

    userData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataMachine(id) {
  try {
    const response = await $api("/master/machines/" + encodeURIComponent(id));

    machine.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

function formatDateTime(date) {
  return moment(date).format("YYYY-MM-DD HH:mm:ss");
}

function handlePartSelected(item) {
  part.value = item;
}

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

onMounted(() => {
  initEditData(route.query.exchangeid);
  fetchUserData();
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
          title: 'Part List',
          class: 'text-h4',
        },
        {
          title: 'Exchange',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VForm ref="form" @submit.prevent="submitData">
    <VCard>
      <VRow class="mx-4 my-4">
        <VCol cols="6">
          <VCardTitle> {{ data?.partname }} </VCardTitle>
          <text class="ml-4"> Part Code : {{ data?.partcode }}</text>
          <VRow class="ml-4 mb-2 mt-6" no-gutters>
            <VCol cols="4"><text> Machine No</text></VCol>
            <VCol
              ><text> : {{ machine?.machineno }}</text></VCol
            >
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Machine Name</text></VCol>
            <VCol
              ><text> : {{ machine?.machinename }}</text></VCol
            >
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Model</text></VCol>
            <VCol
              ><text> : {{ data?.model }}</text></VCol
            >
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Die No</text></VCol>
            <VCol
              ><text> : {{ data?.dieno }}</text></VCol
            >
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Die Unit No</text></VCol>
            <VCol
              ><text> : {{ data?.dieunitno }}</text></VCol
            >
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Process Name</text></VCol>
            <VCol
              ><text> : {{ data?.processname }}</text></VCol
            >
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Exchange Shot No</text></VCol>
            <VCol
              ><text>
                : {{ Intl.NumberFormat().format(data?.counter ?? 0) }}</text
              ></VCol
            >
          </VRow>
        </VCol>
        <VCol cols="6">
          <VCard class="pa-4" variant="flat" style="background-color: #f9f9f9">
            <VCardTitle> Exchange </VCardTitle>
            <VCard
              class="pa-4"
              variant="flat"
              style="background-color: #f4f2f5"
            >
              <AppDateTimePicker
                class="mb-2"
                style="background-color: #ffffff"
                v-model="exchangeDate"
                label="Exchange Date"
                :rules="[requiredValidator]"
                placeholder="2024-01-01 00:00"
                :config="{
                  enableTime: true,
                  dateFormat: 'Y-m-d H:i',
                  time_24hr: true,
                }"
                append-inner-icon="tabler-calendar"
              />
              <VRow class="align-center">
                <VCol>
                  <AppTextField
                    class="mb-2"
                    style="background-color: #ffffff"
                    v-model.number="exchangeQty"
                    label="Exchange Qtty"
                    :rules="[requiredValidator]"
                    placeholder="Input exchange qtty"
                    outlined
                    maxlength="12"
                    @keypress="isNumber($event)"
                  />
                </VCol>
                <VCol>
                  <VBtn
                    class="mt-4"
                    variant="tonal"
                    @click="fetchDataQtyPerDie()"
                  >
                    Get Usage
                  </VBtn>
                </VCol>
              </VRow>

              <VTextarea
                class="mb-2"
                style="background-color: #ffffff"
                label="Reason"
                placeholder="Input reason"
                v-model="reason"
                :rules="[requiredValidator]"
                outlined
                maxlength="50"
              />
            </VCard>
          </VCard>
        </VCol>
      </VRow>
    </VCard>

    <VCol>
      <div class="d-flex justify-start">
        <VBtn type="submit" color="primary" class="mr-4"> Add </VBtn>
        <VBtn variant="outlined" color="error" to="/press-shot/part-list"
          >Cancel</VBtn
        >
      </div>
    </VCol>
  </VForm>

  <SelectInventoryPartDialog
    v-model:isDialogVisible="isSelectInventoryPartDialogVisible"
    @submit="handlePartSelected"
  />
</template>
