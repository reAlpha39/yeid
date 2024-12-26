<script setup>
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "user",
  },
});

const toast = useToast();
const router = useRouter();
const route = useRoute();

const roleAccesses = ["1 (Operator)", "2 (Supervisor)", "3 (Manager)"];

const departments = ref([]);

const form = ref();
const isLoadingEditData = ref(false);

const userId = ref();
const selectedDepartment = ref();
const selectedRoleAccess = ref();
const fullName = ref();
const email = ref();
const phone = ref();
const nik = ref();
const status = ref("Active");
const superAdmin = ref("No");
const password = ref();
const allChecked = ref(false);
const controlAccess = ref({
  masterData: { view: false, create: false, update: false, delete: false },
  user: { view: false, create: false, update: false, delete: false },
  part: { view: false, create: false, update: false, delete: false },
  maintenanceSchedule: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  maintenanceReport: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  correctiveMaintenance: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  inventoryInbound: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  inventoryOutbound: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  inventoryOpname: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  pressShot: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
});

const prevData = ref();
const isEdit = ref(false);

async function addData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    convertSuperAdmin(superAdmin.value);

    let controlAccessJson = JSON.stringify(controlAccess.value);

    if (isEdit.value) {
      const response = await $api(
        "/master/users/" + encodeURIComponent(userId.value),
        {
          method: "PUT",
          body: {
            name: fullName.value,
            email: email.value,
            phone: phone.value,
            nik: nik.value,
            role_access: convertRoleAccess(selectedRoleAccess.value),
            department_id: selectedDepartment.value.id,
            status: convertStatus(status.value),
            control_access: controlAccessJson,
          },
          onResponseError({ response }) {
            toast.error(response._data.message ?? "Failed to save user data");
          },
        }
      );

      toast.success("Edit user success");
    } else {
      const response = await $api("/master/users", {
        method: "POST",
        body: {
          name: fullName.value,
          email: email.value,
          phone: phone.value,
          nik: nik.value,
          role_access: convertRoleAccess(selectedRoleAccess.value),
          department_id: selectedDepartment.value.id,
          status: convertStatus(status.value),
          control_access: controlAccessJson,
          password: password.value,
        },
        onResponseError({ response }) {
          toast.error(response._data.message ?? "Failed to save user data");
        },
      });

      toast.success("Save user success");
    }
    await router.push("/master/user");
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataEdit(id) {
  try {
    userId.value = id;
    const response = await $api("/master/users/" + encodeURIComponent(id));
    // console.log(response.data);
    prevData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataDepartment(id) {
  try {
    if (id) {
      const response = await $api(
        "/master/departments/" + encodeURIComponent(id),
        {
          onResponseError({ response }) {
            toast.error(
              response._data.message ?? "Failed to fetch department data"
            );
          },
        }
      );

      selectedDepartment.value = response.data;
      // console.log(response.data);
      selectedDepartment.value.title =
        response.data.code + " | " + response.data.name;
    } else {
      const response = await $api("/master/departments", {
        onResponseError({ response }) {
          toast.error(
            response._data.message ?? "Failed to fetch department data"
          );
        },
      });

      departments.value = response.data;

      departments.value.forEach((maker) => {
        maker.title = maker.code + " | " + maker.name;
      });
    }
  } catch (err) {
    console.log(err);
  }
}

function convertSuperAdmin(val) {
  if (val === "Yes") {
    controlAccess.value.user.view = true;
    controlAccess.value.user.create = true;
    controlAccess.value.user.update = true;
    controlAccess.value.user.delete = true;
  } else {
    controlAccess.value.user.view = false;
    controlAccess.value.user.create = false;
    controlAccess.value.user.update = false;
    controlAccess.value.user.delete = false;
  }
}

function superAdminType(val) {
  if (val) {
    return "Yes";
  } else {
    return "No";
  }
}

function convertStatus(val) {
  if (val === "Active") {
    return "1";
  } else {
    return "0";
  }
}

function statusType(val) {
  if (val === "0") {
    return "Inactive";
  } else {
    return "Active";
  }
}

function convertRoleAccess(id) {
  switch (id) {
    case "1 (Operator)": // Operator
      return "1";
    case "2 (Supervisor)": // Supervisor
      return "2";
    case "3 (Manager)": //  Manager
      return "3";
    default:
      return "";
  }
}

function roleAccessType(id) {
  switch (id) {
    case "1":
      return "1 (Operator)";
    case "2":
      return "2 (Supervisor)";
    case "3":
      return "3 (Manager)";
    default:
      return "";
  }
}

async function initEditData(id) {
  isLoadingEditData.value = true;
  try {
    await fetchDataEdit(id);
    applyData();
  } finally {
    isLoadingEditData.value = false;
  }
}

async function applyData() {
  const data = prevData.value;

  await fetchDataDepartment(data.department_id);

  let prevControlAccess = JSON.parse(data.control_access);
  controlAccess.value = {
    masterData: prevControlAccess.masterData || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    user: prevControlAccess.user || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    part: prevControlAccess.part || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    maintenanceSchedule: prevControlAccess.maintenanceSchedule || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    maintenanceReport: prevControlAccess.maintenanceReport || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    correctiveMaintenance: prevControlAccess.correctiveMaintenance || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    inventoryInbound: prevControlAccess.inventoryInbound || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    inventoryOutbound: prevControlAccess.inventoryOutbound || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    inventoryOpname: prevControlAccess.inventoryOpname || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    pressShot: prevControlAccess.pressShot || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
  };

  fullName.value = data.name;
  email.value = data.email;
  phone.value = data.phone;
  nik.value = data.nik;
  status.value = statusType(data.status);
  selectedRoleAccess.value = roleAccessType(data.role_access);
  superAdmin.value = superAdminType(controlAccess.value.user.view);
}

const isAllChecked = computed(() => {
  return Object.entries(controlAccess.value).every(([key, value]) => {
    if (key === "user") return true;
    return Object.values(value).every((permission) => permission === true);
  });
});

function handleCheckAll(newValue) {
  const isChecked = newValue;
  allChecked.value = isChecked;

  Object.keys(controlAccess.value).forEach((key) => {
    if (key !== "user") {
      controlAccess.value[key] = {
        view: isChecked,
        create: isChecked,
        update: isChecked,
        delete: isChecked,
      };
    }
  });
}

watch(
  controlAccess,
  () => {
    allChecked.value = isAllChecked.value;
  },
  { deep: true, immediate: true }
);

onMounted(() => {
  fetchDataDepartment();

  const id = route.query.id;
  // console.log("Fetching data for id:", id);
  if (id) {
    isEdit.value = true;
    initEditData(route.query.id);
  }
});
</script>

<template>
  <VBreadcrumbs
    class="px-0 pb-2 pt-0"
    :items="[
      {
        title: 'Master',
        class: 'text-h4',
      },
      {
        title: 'User',
        class: 'text-h4',
      },
      {
        title: isEdit ? 'Update User' : 'Add New User',
        class: 'text-h4',
      },
    ]"
  />

  <div
    v-if="isLoadingEditData"
    class="d-flex flex-column align-center justify-center my-12"
  >
    <VProgressCircular
      indeterminate
      color="primary"
      size="48"
      width="4"
      class="mb-2"
    />
    <VCardText class="text-center text-body-1 text-medium-emphasis">
      Loading data, please wait...
    </VCardText>
  </div>

  <VForm v-else ref="form" lazy-validation>
    <VCard>
      <VCardText>
        <VRow>
          <VCol>
            <AppTextField
              v-model="fullName"
              label="Full Name"
              :rules="[requiredValidator]"
              placeholder="Input full name"
              outlined
              maxlength="64"
            />
          </VCol>
          <VCol>
            <AppTextField
              v-model="email"
              label="Email Address"
              :rules="[requiredValidator]"
              placeholder="Input email address"
              outlined
              maxlength="64"
            />
          </VCol>
          <VCol>
            <AppTextField
              v-model="nik"
              label="NIK"
              :rules="[requiredValidator]"
              placeholder="Input NIK"
              outlined
              maxlength="16"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol>
            <AppTextField
              v-model="password"
              label="Password"
              :rules="isEdit ? [] : [requiredValidator]"
              placeholder="Input password"
              outlined
              maxlength="64"
              :hint="isEdit ? 'Leave empty to keep current password' : ''"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="3">
            <AppTextField
              v-model="phone"
              label="Phone Number"
              :rules="[requiredValidator]"
              placeholder="Input phone number"
              outlined
              maxlength="14"
              @keypress="isNumber($event)"
            />
          </VCol>
          <VCol cols="3">
            <AppAutocomplete
              v-model="selectedDepartment"
              label="Department"
              :rules="[requiredValidator]"
              placeholder="Select deparment"
              item-title="title"
              :items="departments"
              return-object
              outlined
            />
          </VCol>
          <VCol cols="3">
            <AppSelect
              v-model="selectedRoleAccess"
              label="Role Access"
              :rules="[requiredValidator]"
              placeholder="Select role access"
              :items="roleAccesses"
              outlined
            />
          </VCol>

          <VCol>
            <VLabel style="color: #43404f; font-size: 13px">Status</VLabel>
            <VSwitch
              v-model="status"
              :rules="[requiredValidator]"
              :label="status"
              false-value="Inactive"
              true-value="Active"
            ></VSwitch>
          </VCol>
          <VCol>
            <VLabel style="color: #43404f; font-size: 13px">Super Admin</VLabel>
            <VSwitch
              v-model="superAdmin"
              :rules="[requiredValidator]"
              :label="superAdmin"
              false-value="No"
              true-value="Yes"
            ></VSwitch>
          </VCol>
        </VRow>
      </VCardText>

      <VCard class="mx-6 mb-6" variant="outlined">
        <VCardTitle
          class="pb-3 pt-3 mb-3 d-flex justify-space-between align-center"
          style="background-color: #8692d014"
        >
          <div>Fungsional</div>
          <VCheckbox
            class="pr-2"
            label="Check All"
            v-model="allChecked"
            @update:model-value="handleCheckAll"
          />
        </VCardTitle>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <text class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Master Data</strong>
          </text>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.masterData.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.masterData.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.masterData.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.masterData.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <text class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Part</strong>
          </text>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.part.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.part.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.part.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.part.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Maintenance Schedule</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.maintenanceSchedule.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.maintenanceSchedule.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.maintenanceSchedule.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.maintenanceSchedule.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Maintenance Report</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.maintenanceReport.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.maintenanceReport.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.maintenanceReport.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.maintenanceReport.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Corrective Maintenance</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.correctiveMaintenance.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.correctiveMaintenance.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.correctiveMaintenance.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.correctiveMaintenance.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Inventory Inbound</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.inventoryInbound.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.inventoryInbound.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.inventoryInbound.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.inventoryInbound.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Inventory Outbound</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.inventoryOutbound.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.inventoryOutbound.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.inventoryOutbound.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.inventoryOutbound.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Inventory Opname</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.inventoryOpname.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.inventoryOpname.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.inventoryOpname.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.inventoryOpname.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 24px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Press Shot Control</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.pressShot.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.pressShot.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.pressShot.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.pressShot.delete"
          />
        </VRow>
      </VCard>
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addData">Save</VBtn>
        <VBtn variant="outlined" color="error" to="/master/user">Cancel</VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
