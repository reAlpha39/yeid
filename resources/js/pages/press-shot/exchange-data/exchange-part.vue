<script setup>
import moment from "moment";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();
const route = useRoute();

const isSelectInventoryPartDialogVisible = ref(false);
const part = ref();

const form = ref(null);
const now = new Date();

const exchangeDate = ref(formatDateTime(now));
const exchangeQty = ref("0");
const reason = ref(null);

async function submitData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  // await addProductionData();
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
</script>

<template>
  <VForm ref="form" @submit.prevent="submitData">
    <VCard>
      <VRow class="mt-2">
        <VCol>
          <VCardTitle> Part </VCardTitle>
          <text class="ml-4"
            >Select the part to exchange. You can only choose one part.</text
          >
        </VCol>

        <VCol>
          <VRow class="d-flex justify-center py-8 mx-4">
            <VCol class="d-flex justify-end align-center">
              <VBtn @click="$emit('update:isDialogVisible', false)">
                Select Part
              </VBtn>
            </VCol>
          </VRow>
        </VCol>
      </VRow>
      <VDivider />

      <VRow class="mx-4 my-4">
        <VCol cols="6">
          <VCardTitle> Part Name </VCardTitle>
          <text class="ml-4"> Part Code : -</text>
          <VRow class="ml-4 mb-2 mt-6" no-gutters>
            <VCol cols="4"><text> Machine No</text></VCol>
            <VCol><text> : -</text></VCol>
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Machine Name</text></VCol>
            <VCol><text> : -</text></VCol>
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Model</text></VCol>
            <VCol><text> : -</text></VCol>
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Die No</text></VCol>
            <VCol><text> : -</text></VCol>
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Die Unit No</text></VCol>
            <VCol><text> : -</text></VCol>
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Process Name</text></VCol>
            <VCol><text> : -</text></VCol>
          </VRow>
          <VRow class="ml-4 mb-2" no-gutters>
            <VCol cols="4"><text> Exchange Shot No</text></VCol>
            <VCol><text> : -</text></VCol>
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
                :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }"
                append-inner-icon="tabler-calendar"
              />
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
  </VForm>

  <SelectInventoryPartDialog
    v-model:isDialogVisible="isSelectInventoryPartDialogVisible"
    @submit="handlePartSelected"
  />
</template>
