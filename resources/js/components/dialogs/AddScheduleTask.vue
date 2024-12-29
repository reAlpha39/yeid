<script setup>
import { ref, watch } from "vue";
import { useToast } from "vue-toastification";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  task: {
    type: Object,
    required: false,
  },
  weekId: {
    type: Number,
    required: false,
  },
});

const toast = useToast();
const emit = defineEmits(["update:isDialogVisible", "submit"]);

const isDeleteDialogVisible = ref(false);
const users = ref([]);

const refVForm = ref();
const isUpdate = ref(false);
const selectedTaskExecution = ref(null);

const statusRadio = ref("pending");
const changeSchedule = ref(false);
const changeWeek = ref(null);
const note = ref("");
const userSelections = ref([{ id: 0, selected: null }]);

async function fetchDataUsers() {
  try {
    const response = await $api("/master/employees", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    users.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

function resetForm() {
  emit("update:isDialogVisible", false);
  statusRadio.value = "pending";
  changeSchedule.value = false;
  changeWeek.value = null;
  note.value = "";
  refVForm.value?.reset();
}

async function submitData() {
  try {
    const { valid, errors } = await refVForm.value?.validate();
    if (valid === false) {
      return;
    }

    // Get all selected employee codes
    const assignedEmployees = userSelections.value
      .map((selection) => selection.selected?.employeecode)
      .filter((code) => code);

    const payload = {
      task_id: props.task.task_id,
      scheduled_week: props.weekId,
      status: statusRadio.value,
      note: note.value,
      completion_week: completion_week_change(),
      assigned_employees: assignedEmployees,
    };

    await $api("/schedule/task/executions", {
      method: "POST",
      body: payload,
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    toast.success("Data added successfully");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    toast.error(err.message || "Failed to save schedule task");
    console.log(err);
  }
}

function completion_week_change() {
  if (statusRadio.value === "overdue") {
    return changeWeek.value.id;
  }

  if (statusRadio.value === "completed") {
    return props.weekId;
  }

  return null;
}

// Add new function to handle adding more user selections
function handleAddManPower() {
  userSelections.value.push({
    id: userSelections.value.length,
    selected: null,
  });
}

// Optional: Function to remove a user selection
function handleRemoveManPower(index) {
  if (userSelections.value.length > 1) {
    userSelections.value = userSelections.value.filter((_, i) => i !== index);
  }
}

// Get all selected users (useful for form submission)
const getAllSelectedUsers = computed(() => {
  return userSelections.value
    .map((selection) => selection.selected)
    .filter((user) => user !== null);
});

watch(
  () => props.isDialogVisible,
  async (value) => {
    if (value) {
      refVForm.value?.reset();
      statusRadio.value = "pending";
      changeSchedule.value = false;
      changeWeek.value = null;
      note.value = "";
      userSelections.value = [{ id: 0, selected: null }];
      fetchDataUsers();
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
                <h4 class="text-h4 mb-2">Create Schedule Task</h4>
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

            <div
              v-for="(selection, index) in userSelections"
              :key="selection.id"
              class="mx-6 my-3"
            >
              <div class="d-flex align-center gap-2">
                <AppAutocomplete
                  v-model="selection.selected"
                  label="Select Man Power"
                  placeholder="Select man power"
                  :rules="[requiredValidator]"
                  :items="users"
                  item-title="employeename"
                  return-object
                  outlined
                  class="flex-grow-1"
                />

                <VBtn
                  v-if="index > 0"
                  icon
                  variant="text"
                  color="error"
                  class="mt-3"
                  @click="handleRemoveManPower(index)"
                >
                  <VIcon>tabler-trash</VIcon>
                </VBtn>
              </div>
            </div>

            <!-- Add Man Power button -->
            <div class="mx-3">
              <VBtn
                variant="text"
                color="primary"
                prepend-icon="tabler-plus"
                @click="handleAddManPower"
              >
                Add Man Power
              </VBtn>
            </div>

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
