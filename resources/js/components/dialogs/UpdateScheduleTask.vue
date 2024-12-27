<script setup>
import { ref, watch } from "vue";
import { useToast } from "vue-toastification";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  id: {
    type: Number,
    required: false,
  },
});

const toast = useToast();
const emit = defineEmits(["update:isDialogVisible", "submit"]);

const isDeleteDialogVisible = ref(false);

const refVForm = ref();
const isUpdate = ref(false);
const selectedTaskExecution = ref(null);

const statusRadio = ref("pending");
const changeSchedule = ref(false);
const changeWeek = ref(null);
const note = ref("");

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

async function fetchDataTaskExecution(id) {
  try {
    const response = await $api("/schedule/task/executions/" + id, {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    selectedTaskExecution.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

function applyData() {
  statusRadio.value = selectedTaskExecution.value.status;
  changeWeek.value =
    selectedTaskExecution.value.completion_week === null
      ? null
      : weekOfYear[selectedTaskExecution.value.completion_week - 1];
  note.value = selectedTaskExecution.value.note;
}

function resetForm() {
  emit("update:isDialogVisible", false);
  selectedTaskExecution.value = null;
  statusRadio.value = "pending";
  changeSchedule.value = false;
  changeWeek.value = null;
  note.value = "";
  refVForm.value?.reset();
}

async function submitData() {
  const { valid, errors } = await refVForm.value?.validate();
  if (valid === false) {
    return;
  }

  if (isUpdate.value) {
    await update();
  } else {
    await add();
  }
}

async function add() {
  try {
    await $api("/schedule/task/executions", {
      method: "POST",
      body: {
        task_id: selectedTaskExecution.value.task_id,
        status: statusRadio.value,
        scheduled_week: changeWeek.value.id,
        note: note.value,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    toast.success("Data added successfully");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    console.log(err);
  }
}

async function update() {
  try {
    await $api(
      "/schedule/task/executions/" + selectedTaskExecution.value.item_id,
      {
        method: "PUT",
        body: {
          task_id: selectedTaskExecution.value.task_id,
          status: statusRadio.value,
          scheduled_week: changeSchedule.value ? changeWeek.value.id : null,
          completion_week: completion_week_change(),
          note: note.value,
        },
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      }
    );

    toast.success("Data updated successfully");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    console.log(err);
  }
}

async function deleteScheduleItem() {
  try {
    const result = await $api(
      "/schedule/task/executions/" + selectedTaskExecution.value.item_id,
      {
        method: "DELETE",

        onResponseError({ response }) {
          toast.error(response._data.message);
          // errors.value = response._data.errors;
        },
      }
    );

    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

function openDeleteDialog() {
  isDeleteDialogVisible.value = true;
}

function completion_week_change() {
  if (statusRadio.value === "overdue") {
    return changeWeek.value;
  }

  if (statusRadio.value === "completed") {
    return selectedTaskExecution.value.scheduled_week;
  }

  return null;
}

watch(
  () => props.isDialogVisible,
  async (value) => {
    if (value) {
      refVForm.value?.reset();
      changeSchedule.value = false;
      isUpdate.value = false;
      if (props.id) {
        isUpdate.value = true;
        await fetchDataTaskExecution(props.id);
        applyData();
      }
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
    <VForm ref="refVForm" @submit.prevent="submitData">
      <VCard class="dialog-card pa-6">
        <div class="dialog-content">
          <VCardText class="mb-6">
            <VRow>
              <VCol cols="8">
                <h4 class="text-h4 mb-2">Update Schedule Task</h4>
              </VCol>
              <VCol cols="4" class="d-flex justify-end">
                <VBtn
                  variant="tonal"
                  prepend-icon="tabler-trash"
                  @click="openDeleteDialog"
                >
                  Hapus Schedule
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>

          <VCard variant="outlined" class="mb-6 mx-6 pa-6">
            <VCardText>
              <h4 class="text-h4 mb-2">Status Pekerjaan</h4>
              <p class="text-body-1 text-left">
                Tentukan status sesuai dengan progres pekerjaan saat ini
              </p>
            </VCardText>

            <VRadioGroup
              v-model="statusRadio"
              inline
              class="mb-6"
              :rules="[requiredValidator]"
            >
              <VRow class="mx-2">
                <VCol>
                  <VCard
                    class="px-2 pt-3 pb-2"
                    variant="outlined"
                    :class="{ 'active-card': statusRadio === 'pending' }"
                  >
                    <VRadio label="Belum dilakukan" value="pending" />
                  </VCard>
                </VCol>
                <VCol>
                  <VCard
                    class="px-2 pt-3 pb-2"
                    variant="outlined"
                    :class="{ 'active-card': statusRadio === 'completed' }"
                  >
                    <VRadio
                      label="Sudah dilakukan (on time)"
                      value="completed"
                    />
                  </VCard>
                </VCol>
                <VCol>
                  <VCard
                    class="px-2 pt-3 pb-2"
                    variant="outlined"
                    :class="{ 'active-card': statusRadio === 'overdue' }"
                  >
                    <VRadio label="Sudah dilakukan (delay)" value="overdue" />
                  </VCard>
                </VCol>
              </VRow>
            </VRadioGroup>

            <AppAutocomplete
              v-if="statusRadio === 'overdue'"
              class="mb-6 px-5"
              v-model="changeWeek"
              label="Week"
              placeholder="Select week"
              :items="weekOfYear"
              item-title="title"
              :rules="statusRadio === 'overdue' ? [requiredValidator] : []"
              return-object
              outlined
            />

            <VCard
              variant="outlined"
              class="mx-5 pa-6 mb-6"
              style="background-color: #f9f9f9"
            >
              <VCheckbox
                class="mb-2"
                label="Pindah Jadwal"
                v-model="changeSchedule"
              />

              <AppAutocomplete
                v-if="changeSchedule"
                class="mb-6 px-2"
                v-model="changeWeek"
                label="Week"
                placeholder="Select week"
                :items="weekOfYear"
                item-title="title"
                return-object
                outlined
                style="background-color: #ffffff"
              />
            </VCard>

            <AppTextarea
              class="mx-4 my-6"
              label="Catatan"
              placeholder="input catatan"
              v-model="note"
              :rules="[requiredValidator]"
              outlined
              maxlength="255"
            />
          </VCard>

          <VRow class="mb-6">
            <VCol cols="6">
              <div class="d-flex justify-end">
                <VBtn type="submit" color="success"> Submit </VBtn>
              </div>
            </VCol>

            <VCol cols="6">
              <VBtn color="error" variant="tonal" @click="resetForm">
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </div>
      </VCard>
    </VForm>
  </VDialog>

  <VDialog v-model="isDeleteDialogVisible" max-width="500px">
    <VCard class="pa-4">
      <VCardTitle class="text-center">
        Are you sure you want to delete this item?
      </VCardTitle>

      <VCardActions class="pt-4">
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="isDeleteDialogVisible = !isDeleteDialogVisible"
        >
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="deleteScheduleItem()">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.dialog-card {
  max-height: 90vh; /* Set maximum height to 90% of viewport height */
  display: flex;
  flex-direction: column;
}

.dialog-content {
  overflow-y: auto; /* Enable vertical scrolling */
  padding: 1.5rem; /* Equivalent to pa-6 */
}

/* Your existing styles */
.actions-column {
  text-align: right;
  width: 100px;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
}

.v-card {
  border-color: #ccc;
}

.active-card {
  border-color: #fa0202 !important;
}
</style>
