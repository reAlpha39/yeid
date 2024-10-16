<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  item: {
    type: Object,
    required: false,
  },
});

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const employees = ref([]);

const refVForm = ref();

const workTime = ref({
  employee: undefined,
  staffname: undefined,
  inactivetime: 0,
  periodicaltime: 0,
  questiontime: 0,
  preparetime: 0,
  checktime: 0,
  waittime: 0,
  repairtime: 0,
  confirmtime: 0,
});

const isUpdate = ref(false);

async function fetchEmployee(id) {
  try {
    if (id) {
      const response = await $api("/master/employees/", {
        params: {
          search: id,
        },
      });

      workTime.value.employee = response.data[0];
    } else {
      const response = await $api("/master/employees");

      employees.value = response.data;
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

async function submitData() {
  const { valid, errors } = await refVForm.value?.validate();
  if (valid === false) {
    return;
  }

  emit("update:isDialogVisible", false);
  emit("submit", workTime.value);

  workTime.value = {
    employee: undefined,
    staffname: undefined,
    inactivetime: 0,
    periodicaltime: 0,
    questiontime: 0,
    preparetime: 0,
    checktime: 0,
    waittime: 0,
    repairtime: 0,
    confirmtime: 0,
  };
}

function handleEmployeeSelection(val) {
  workTime.value.staffname = val.employeename;
}

const resetForm = () => {
  emit("update:isDialogVisible", false);
  workTime.value = {
    employee: undefined,
    staffname: undefined,
    inactivetime: 0,
    periodicaltime: 0,
    questiontime: 0,
    preparetime: 0,
    checktime: 0,
    waittime: 0,
    repairtime: 0,
    confirmtime: 0,
  };
};

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      fetchEmployee();
      console.log("Dialog opened with id:", props.item?.workid);

      workTime.value = {
        employee: undefined,
        staffname: undefined,
        inactivetime: 0,
        periodicaltime: 0,
        questiontime: 0,
        preparetime: 0,
        checktime: 0,
        waittime: 0,
        repairtime: 0,
        confirmtime: 0,
      };

      if (props.item?.workid) {
        fetchEmployee(props.item?.staffname);
        workTime.value = { ...props.item };
        isUpdate.value = true;
      } else {
        isUpdate.value = false;
      }
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
    <VForm ref="refVForm" @submit.prevent="submitData">
      <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

      <VCard class="share-project-dialog pa-2 pa-sm-10">
        <VCardText>
          <h4 class="text-h4 text-center mb-2">Detail Maintenance Request</h4>
        </VCardText>

        <VCardText>
          <p class="text-center">
            Tambahkan waktu pekerjaan maintenance dengan data yang valid dan
            akurat.
          </p>
        </VCardText>

        <AppAutocomplete
          class="mb-4"
          v-model="workTime.employee"
          label="Nama"
          :rules="[requiredValidator]"
          placeholder="Pilih nama"
          item-title="employeename"
          :items="employees"
          outlined
          return-object
          @update:model-value="handleEmployeeSelection"
        />

        <VRow>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.inactivetime"
              label="Waktu Sebelum Pekerjaan (menit)"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.periodicaltime"
              label="Waktu Periodik Maintenance (menit)"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.questiontime"
              label="Waktu Pertanyaan (menit)"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.preparetime"
              label="Waktu Siapkan (menit))"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.checktime"
              label="Waktu Penelitian (menit)"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.waittime"
              label="Waktu Menunggu Part (menit))"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.repairtime"
              label="Waktu Pekerjaan Maintenance (menit)"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol cols="6">
            <AppTextField
              v-model.number="workTime.confirmtime"
              label="Waktu Konfirmasi (menit))"
              placeholder="0"
              maxlength="4"
              @keypress="isNumber($event)"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="6">
            <div class="d-flex justify-end">
              <VBtn type="submit" color="primary"> Submit </VBtn>
            </div>
          </VCol>

          <VCol cols="6">
            <VBtn color="error" variant="tonal" @click="resetForm">
              Discard
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VForm>
  </VDialog>
</template>
