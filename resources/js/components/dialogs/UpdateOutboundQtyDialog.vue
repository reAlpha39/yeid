<script setup>
import AppAutocomplete from "@/@core/components/app-form-elements/AppAutocomplete.vue";
import { useToast } from "vue-toastification";
import { VBtn, VRow } from "vuetify/lib/components/index.mjs";

const toast = useToast();

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  recordId: {
    type: String,
    required: true,
  },
  partCode: {
    type: String,
    required: true,
  },
  machineNo: {
    type: String,
    required: true,
  },
  quantity: {
    required: false,
  },
});

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const machines = ref([]);
const selectedMachine = ref(null);
const quantity = ref("");

async function saveUpdatedQty() {
  try {
    await $api("/inventory/update-inv-outbound", {
      method: "POST",
      body: {
        record_id: props.recordId,
        machine_no: selectedMachine.value.machineno,
        machine_name: selectedMachine.value.machinename,
        quantity: quantity.value,
      },

      onResponseError({ response }) {
        errors.value = response._data.message;
      },
    });

    toast.success("Update outbound success");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    console.log(err);
  }
}

async function getMachines(partCode) {
  try {
    const response = await $api("/getMachines", {
      method: "GET",
      params: {
        partCode: partCode,
      },

      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    machines.value = response.data;

    machines.value.forEach((e) => {
      e.title = e.machineno + " | " + e.machinename;

      if (e.machineno === props.machineNo) {
        selectedMachine.value = e;
      }
    });
  } catch (err) {
    console.log(err);
  }
}

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      quantity.value = props.quantity;
      getMachines(props.partCode);
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

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Adjustment Out-Bound Data</h4>
      </VCardText>

      <div class="pa-4 rounded" style="background-color: #4b3e6414">
        <AppAutocomplete
          v-model="selectedMachine"
          placeholder="Machine"
          item-title="title"
          :items="machines"
          return-object
          outlined
          style="background-color: #ffffff"
        />

        <div class="pa-4 mt-4 rounded" style="background-color: #ffffff">
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Machine Name </VCol>
            <VCol cols="12" md="8">
              : {{ selectedMachine?.machinename ?? "-" }}
            </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Shop Code </VCol>
            <VCol> : {{ selectedMachine?.shopcode ?? "-" }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Shop Name </VCol>
            <VCol> : {{ selectedMachine?.shopname ?? "-" }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Model Name </VCol>
            <VCol> : {{ selectedMachine?.modelname ?? "-" }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Maker Name </VCol>
            <VCol> : {{ selectedMachine?.makername ?? "-" }} </VCol>
          </VRow>
          <VRow class="no-gutters">
            <VCol cols="12" md="4"> Line Code </VCol>
            <VCol> : {{ selectedMachine?.linecode ?? "-" }} </VCol>
          </VRow>
        </div>
        <br />
      </div>

      <div
        class="pa-4 my-4 rounded"
        style="background-color: #e8776814; opacity: 8"
      >
        <VRow class="d-flex">
          <VCol> Outbound Quantity </VCol>
          <VCol>
            <div style="text-align: right">
              <strong>
                {{ formatNumber(props.quantity) }}
              </strong>
            </div>
          </VCol>
        </VRow>
      </div>

      <VRow class="d-flex">
        <VCol> Adjustment outbound to </VCol>
        <VCol cols="12" md="2">
          <AppTextField
            v-model.number="quantity"
            type="number"
            placeholder="5"
            min="0"
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
