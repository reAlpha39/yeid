<script setup>
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

const weekOfYear = [
  { id: 1, title: "Jan Week 1" },
  { id: 2, title: "Jan Week 2" },
  { id: 3, title: "Jan Week 3" },
  { id: 4, title: "Jan Week 4" },
  { id: 5, title: "Feb Week 1" },
  { id: 6, title: "Feb Week 2" },
  { id: 7, title: "Feb Week 3" },
  { id: 8, title: "Feb Week 4" },
  { id: 9, title: "Mar Week 1" },
  { id: 10, title: "Mar Week 2" },
  { id: 11, title: "Mar Week 3" },
  { id: 12, title: "Mar Week 4" },
  { id: 13, title: "Apr Week 1" },
  { id: 14, title: "Apr Week 2" },
  { id: 15, title: "Apr Week 3" },
  { id: 16, title: "Apr Week 4" },
  { id: 17, title: "May Week 1" },
  { id: 18, title: "May Week 2" },
  { id: 19, title: "May Week 3" },
  { id: 20, title: "May Week 4" },
  { id: 21, title: "Jun Week 1" },
  { id: 22, title: "Jun Week 2" },
  { id: 23, title: "Jun Week 3" },
  { id: 24, title: "Jun Week 4" },
  { id: 25, title: "Jul Week 1" },
  { id: 26, title: "Jul Week 2" },
  { id: 27, title: "Jul Week 3" },
  { id: 28, title: "Jul Week 4" },
  { id: 29, title: "Aug Week 1" },
  { id: 30, title: "Aug Week 2" },
  { id: 31, title: "Aug Week 3" },
  { id: 32, title: "Aug Week 4" },
  { id: 33, title: "Sep Week 1" },
  { id: 34, title: "Sep Week 2" },
  { id: 35, title: "Sep Week 3" },
  { id: 36, title: "Sep Week 4" },
  { id: 37, title: "Oct Week 1" },
  { id: 38, title: "Oct Week 2" },
  { id: 39, title: "Oct Week 3" },
  { id: 40, title: "Oct Week 4" },
  { id: 41, title: "Nov Week 1" },
  { id: 42, title: "Nov Week 2" },
  { id: 43, title: "Nov Week 3" },
  { id: 44, title: "Nov Week 4" },
  { id: 45, title: "Dec Week 1" },
  { id: 46, title: "Dec Week 2" },
  { id: 47, title: "Dec Week 3" },
  { id: 48, title: "Dec Week 4" },
];
const frequencyPeriods = ["week", "month", "year"];
const users = ref([]);
const isSelectMachineDialogVisible = ref(false);
const isSelectScheduleActivityDialogVisible = ref(false);
const selectedMachine = ref(null);
const selectedActivity = ref(null);
const selectedShop = ref(null);

const machines = ref([]);
const taskItem = ref(null);
const cycleTime = ref(null);
const frequencyTimes = ref(null);
const frequencyPeriod = ref(null);
const startingWeek = ref(null);
// Change to array to store multiple user selections
const userSelections = ref([{ id: 0, selected: null }]);

async function fetchDataUsers() {
  try {
    const response = await $api("/master/users", {
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

function handleMachinesSelected(item) {
  selectedMachine.value = item;
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

function handleActivitySelected(item) {
  selectedActivity.value = item;
  selectedShop.value = item.shop.shopcode;
}

onMounted(() => {
  fetchDataUsers();
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Schedule',
          class: 'text-h4',
        },
        {
          title: 'Add',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VForm>
    <div class="mb-6">
      <VCard v-if="selectedActivity">
        <VRow class="d-flex justify-space-between align-center py-4">
          <VCol cols="6">
            <VRow no-gutters>
              <VCol cols="12">
                <VCardTitle class="ml-2">{{
                  selectedActivity?.activity_name ?? "-"
                }}</VCardTitle>
              </VCol>
            </VRow>
            <VRow class="ml-6" no-gutters>
              <VCol cols="12">
                <text>{{ selectedActivity?.shop.shopname ?? "-" }}</text>
              </VCol>
            </VRow>
            <VRow class="ml-6" no-gutters>
              <VCol cols="12">
                <small>{{ selectedActivity?.shop_id ?? "-" }}</small>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="auto" class="mr-4">
            <VBtn
              prepend-icon="tabler-plus"
              @click="
                isSelectScheduleActivityDialogVisible =
                  !isSelectScheduleActivityDialogVisible
              "
            >
              Change Activity
            </VBtn>
          </VCol>
        </VRow>
      </VCard>

      <VCard v-else>
        <VRow class="d-flex justify-space-between align-center mb-4 pt-4 pb-2">
          <VCol cols="6">
            <VCardTitle class="ml-2"> Activity </VCardTitle>
            <small class="ml-6"
              >Activity is required, please select one activity</small
            >
          </VCol>
          <VCol class="mr-4" cols="auto">
            <VBtn
              v-if="selectedActivity === null"
              prepend-icon="tabler-plus"
              @click="
                isSelectScheduleActivityDialogVisible =
                  !isSelectScheduleActivityDialogVisible
              "
            >
              Add Activity
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </div>

    <VCard class="mb-6">
      <VCard
        v-if="selectedMachine"
        variant="outlined"
        class="ma-6"
        style="background-color: #e8776814"
      >
        <VRow class="d-flex justify-space-between align-center mb-4 pt-4">
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
            <VRow class="ml-4" no-gutters>
              <VCol cols="12">
                <small>Model : {{ selectedMachine?.modelname ?? "=" }}</small>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="auto" class="mr-4">
            <VBtn
              prepend-icon="tabler-plus"
              @click="
                isSelectMachineDialogVisible = !isSelectMachineDialogVisible
              "
            >
              Change Machine
            </VBtn>
          </VCol>
        </VRow>
      </VCard>

      <VCard
        v-else
        variant="outlined"
        class="ma-6"
        style="background-color: #8692d014"
      >
        <VRow class="d-flex justify-space-between align-center mb-4 pt-4 pb-2">
          <VCol cols="6">
            <VCardTitle> Machine </VCardTitle>
            <small class="ml-4"
              >Machine is required, please select one machine</small
            >
          </VCol>
          <VCol class="mr-4" cols="auto">
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
      </VCard>

      <div class="py-4" />

      <VTextarea
        class="mx-6"
        label="Task/Item"
        placeholder="Input task/item"
        v-model="taskItem"
        :rules="[requiredValidator]"
        outlined
        maxlength="255"
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
            item-title="name"
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

      <div class="py-4" />

      <AppTextField
        class="mx-6 mb-6"
        v-model.number="cycleTime"
        label="Cycle Time (CT)"
        :rules="[requiredValidator]"
        placeholder="Input cycle time"
        outlined
        maxlength="12"
        @keypress="isNumber($event)"
      />

      <VCard variant="outlined" class="mx-6 mb-6">
        <VCardText> Setup Periode </VCardText>
        <VCardText>
          <div class="d-flex align-center gap-4">
            <span>1x in</span>
            <AppTextField
              v-model.number="frequencyTimes"
              :rules="[requiredValidator]"
              placeholder="Input frequency times"
              outlined
              maxlength="2"
              @keypress="isNumber($event)"
            />
            <AppAutocomplete
              v-model="frequencyPeriod"
              placeholder="Select frequency period"
              :rules="[requiredValidator]"
              :items="frequencyPeriods"
              outlined
              class="flex-grow-1"
            />
          </div>
        </VCardText>
      </VCard>

      <AppAutocomplete
        class="mx-6 mb-6"
        v-model="startingWeek"
        placeholder="Select starting week"
        :rules="[requiredValidator]"
        :items="weekOfYear"
        item-title="title"
        return-object
        outlined
      />
    </VCard>
  </VForm>

  <SelectMachineDialog
    v-model:isDialogVisible="isSelectMachineDialogVisible"
    v-model:items="machines"
    v-model:shopcode="selectedShop"
    @submit="handleMachinesSelected"
  />

  <SelectScheduleActivity
    v-model:isDialogVisible="isSelectScheduleActivityDialogVisible"
    @submit="handleActivitySelected"
  />
</template>
