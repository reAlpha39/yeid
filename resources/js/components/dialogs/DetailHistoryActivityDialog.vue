<script setup>
import moment from "moment";

const data = ref(null);
const machine = ref(null);

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  data: {
    required: false,
  },
});

async function fetchDataMachine(id) {
  try {
    const response = await $api("/master/machines/" + id);

    machine.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

function formatCustomDate(dateString) {
  let momentDate;

  // Check if the date is in numeric format (20241105094958)
  if (/^\d{14}$/.test(dateString)) {
    momentDate = moment(dateString, "YYYYMMDDHHmmss");
  }
  // Check if the date is in YYYY-MM-DD HH:mm:ss format
  else if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateString)) {
    momentDate = moment(dateString, "YYYY-MM-DD HH:mm:ss");
  } else {
    return "Invalid date format";
  }

  moment.locale("id");

  return momentDate.format("DD MMMM YYYY, HH:mm:ss");
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      data.value = props.data;
      fetchDataMachine(data.value.machineno);
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
        <h4 class="text-h4 text-center mb-2">Detail History Activity</h4>
      </VCardText>

      <VCard variant="outlined" style="background-color: #f9f9f9">
        <VCardText>
          <VRow no-gutters>
            <VCol>
              <p>Machine No</p>
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ machine?.machineno ?? "-" }}</span
              >
            </VCol>
            <VCol>
              <p>Machine Name</p>
              <span
                class="d-block font-weight-medium text-high-emphasis text-truncate"
                >{{ machine?.machinename ?? "-" }}</span
              >
            </VCol>
          </VRow>
        </VCardText>

        <VCard class="mx-6 pa-6 mb-6" flat>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Date Time</text>
            </VCol>
            <VCol>
              <text>: {{ formatCustomDate(data?.datetime) }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Part Code</text>
            </VCol>
            <VCol>
              <text>: {{ data?.partcode ?? "-" }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Part Name</text>
            </VCol>
            <VCol>
              <text>: {{ data?.partname ?? "-" }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Process</text>
            </VCol>
            <VCol>
              <text>: {{ data?.processname ?? "-" }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Model</text>
            </VCol>
            <VCol>
              <text>: {{ data?.model ?? "-" }}</text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Die#</text>
            </VCol>
            <VCol no-gutters>
              <text>: {{ data?.dieno ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Qty/Die#</text>
            </VCol>
            <VCol>
              <text>: {{ data?.qty ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Main Form</text>
            </VCol>
            <VCol>
              <text>: {{ data?.mainform ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Sub Menu</text>
            </VCol>
            <VCol>
              <text>: {{ data?.submenu ?? "-" }} </text>
            </VCol>
          </VRow>
          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Reason Activity</text>
            </VCol>
            <VCol>
              <text>: {{ data?.reason ?? "-" }} </text>
            </VCol>
          </VRow>

          <VRow class="py-1" no-gutters>
            <VCol cols="3">
              <text>Update Time</text>
            </VCol>
            <VCol>
              <text>: {{ formatCustomDate(data?.updatetime) }} </text>
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
