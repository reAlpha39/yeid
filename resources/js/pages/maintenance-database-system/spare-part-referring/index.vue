<script setup>
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "mtDbsSparePart",
  },
});
const toast = useToast();
const currentTab = ref("window1");

const onUpdate = ref(false);
const progress = ref(0.0);
const progressInterval = ref(null);
const isLoading = ref(true);

async function initUpdateData() {
  try {
    onUpdate.value = true;
    progress.value = 0;

    const response = await $api("/inventory/update-summary", {
      method: "POST",
      params: {
        shift_flag: true,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    startProgressPolling();
    toast.success(response.message);
  } catch (err) {
    console.log(err);
    onUpdate.value = false;
  }
}

function startProgressPolling() {
  if (progressInterval.value) {
    clearInterval(progressInterval.value);
  }

  progressInterval.value = setInterval(async () => {
    await updateProgress();
  }, 2000);
}

async function updateProgress() {
  try {
    const response = await $api("/inventory/update-progress", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    if (response.data.status === "completed") {
      onUpdate.value = false;
      if (progressInterval.value) {
        clearInterval(progressInterval.value);
        progressInterval.value = null;
      }
    } else {
      onUpdate.value = true;
      progress.value = response.data.progress;
    }
  } catch (err) {
    console.log(err);
    if (progressInterval.value) {
      clearInterval(progressInterval.value);
      progressInterval.value = null;
    }
    onUpdate.value = false;
  } finally {
    isLoading.value = false; // Set loading to false after check completes
  }
}

onBeforeUnmount(() => {
  if (progressInterval.value) {
    clearInterval(progressInterval.value);
    progressInterval.value = null;
  }
});

onMounted(async () => {
  isLoading.value = true;
  await updateProgress(); // Wait for the initial check to complete

  if (onUpdate.value === true) {
    startProgressPolling();
  }
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Maintenance Database System',
          class: 'text-h4',
        },
        {
          title: 'Spare Part Referring',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <div class="d-flex flex-wrap gap-4">
    <VTabs v-model="currentTab" class="v-tabs-pill">
      <VTab class="pb-2">Control Const<br />Spare Parts</VTab>
      <VTab class="pb-2">Result Const of<br />Used Parts</VTab>
      <VTab class="pb-2">Check Running<br />Const of Machines</VTab>
      <VTab class="pb-2">Inventory Cost<br />Change List(Top 100)</VTab>
    </VTabs>

    <VSpacer />

    <VBtn
      prepend-icon="tabler-refresh"
      @click="initUpdateData"
      :loading="onUpdate"
    >
      Update
    </VBtn>
  </div>

  <VProgressCircular
    v-if="isLoading"
    indeterminate
    color="primary"
    class="mt-4"
  ></VProgressCircular>

  <div v-else>
    <div v-if="onUpdate">
      <VCard class="mt-6">
        <VCardTitle>
          <div class="text-center mb-2">
            Processing: {{ Math.round(progress) }}%
          </div>
          <VProgressLinear
            class="my-6"
            :model-value="progress"
            color="primary"
            striped
          />
        </VCardTitle>
      </VCard>
    </div>

    <div v-else>
      <VWindow v-model="currentTab">
        <VWindowItem key="`window1`">
          <ControlConstSparePartsTab />
        </VWindowItem>
        <VWindowItem key="`window2`">
          <ResultConstOfUsedPartsTab />
        </VWindowItem>
        <VWindowItem key="`window3`">
          <CheckRunningConstOfMachinesTab />
        </VWindowItem>
        <VWindowItem key="`window4`">
          <InventoryConstChangeListTab />
        </VWindowItem>
      </VWindow>
    </div>
  </div>
</template>
