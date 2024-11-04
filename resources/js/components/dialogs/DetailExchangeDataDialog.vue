<script setup>
const data = ref();
const machine = ref();

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  id: {
    required: false,
  },
});

async function initData(id) {
  await fetchData(id);
  await fetchDataMachine(data.value?.machineno);
}

async function fetchData(id) {
  try {
    const response = await $api("/press-shot/exchanges/" + id);

    data.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataMachine(id) {
  try {
    const response = await $api("/master/machines/" + id);

    machine.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

function formatDateTime(datetimeString) {
  if ((datetimeString ?? "") === "") {
    const formattedDate = "";
    const formattedTime = "";

    return { formattedDate, formattedTime };
  }
  // Parse the datetime string
  const year = datetimeString.slice(0, 4);
  const month = datetimeString.slice(4, 6);
  const day = datetimeString.slice(6, 8);
  const hours = datetimeString.slice(8, 10);
  const minutes = datetimeString.slice(10, 12);
  const seconds = datetimeString.slice(12, 14);

  // Create a Date object
  const date = new Date(
    `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`
  );

  // Define Indonesian locale options for date and time formatting
  const dateFormatter = new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });

  const timeFormatter = new Intl.DateTimeFormat("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });

  // Format the date and time
  const formattedDate = dateFormatter.format(date);
  const formattedTime = timeFormatter.format(date);

  return { formattedDate, formattedTime };
}

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      initData(props.id);
      console.log("Dialog opened with id:", props.id);
    }
  }
);
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 1200"
    @update:model-value="dialogVisibleUpdate"
  >
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Detail Exchange Part</h4>
      </VCardText>

      <VCard variant="outlined" style="background-color: #f9f9f9">
        <VCardText>
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            >{{ data?.partname ?? "-" }}</span
          >
          <p>{{ data?.partcode ?? "-" }}</p>
        </VCardText>

        <VCard class="mx-6 pa-6 mb-6" flat>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Exchange Date</text>
            </VCol>
            <VCol>
              <text
                >: {{ formatDateTime(data?.exchangedatetime).formattedDate }},
                {{ formatDateTime(data?.exchangedatetime).formattedTime }}</text
              >
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Exchange Qtty</text>
            </VCol>
            <VCol no-gutters>
              <text>: {{ data?.exchangeqtty ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Reason</text>
            </VCol>
            <VCol>
              <text>: {{ data?.reason ?? "-" }} </text>
            </VCol>
          </VRow>
        </VCard>

        <VCard class="mx-6 pa-6 mb-6" flat>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Machine No</text>
            </VCol>
            <VCol>
              <text>: {{ data?.machineno ?? "-" }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Machine Name</text>
            </VCol>
            <VCol no-gutters>
              <text>: {{ machine?.machinename ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Model</text>
            </VCol>
            <VCol>
              <text>: {{ data?.model ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Die No</text>
            </VCol>
            <VCol>
              <text>: {{ data?.dieno ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Die Unit No</text>
            </VCol>
            <VCol>
              <text>: {{ data?.dieunitno ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Process Name</text>
            </VCol>
            <VCol>
              <text>: {{ data?.processname ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Exchange Slot No</text>
            </VCol>
            <VCol>
              <text>: {{ data?.exchangeshotno ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Serial No</text>
            </VCol>
            <VCol>
              <text>: {{ data?.serialno ?? "-" }} </text>
            </VCol>
          </VRow>
        </VCard>

        <VCard class="mx-6 pa-6 mb-6" flat>
          <text
            class="mb-2 d-block font-weight-medium text-high-emphasis text-truncate"
            style="font-size: 18px"
            >Employee</text
          >

          <text
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            style="font-size: 15px"
            >{{ data?.employeename ?? "-" }}</text
          >

          <text>
            {{ data?.employeecode ?? "-" }}
          </text>
        </VCard>
      </VCard>

      <VRow class="d-flex justify-center py-8">
        <VCol class="d-flex justify-center align-center">
          <VBtn @click="$emit('update:isDialogVisible', false)"> Close </VBtn>
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>
