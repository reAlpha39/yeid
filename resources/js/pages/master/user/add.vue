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
  masterDataPart: { view: false, create: false, update: false, delete: false },
  invControlPartList: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  invControlMasterPart: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  invControlInbound: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  invControlOutbound: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  pressShotPartList: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  pressShotExcData: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  pressShotProdData: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  pressShotMasterPart: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  pressShotHistoryAct: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  mtDbsDeptReq: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  mtDbsMtReport: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  mtDbsReqWork: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  mtDbsDbAnl: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  mtDbsSparePart: {
    view: false,
    create: false,
    update: false,
    delete: false,
  },
  schedule: {
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
            password: password.value,
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
    masterDataPart: prevControlAccess.masterDataPart || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    invControlPartList: prevControlAccess.invControlPartList || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    invControlMasterPart: prevControlAccess.invControlMasterPart || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    invControlInbound: prevControlAccess.invControlInbound || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    invControlOutbound: prevControlAccess.invControlOutbound || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    pressShotPartList: prevControlAccess.pressShotPartList || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    pressShotExcData: prevControlAccess.pressShotExcData || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    pressShotProdData: prevControlAccess.pressShotProdData || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    pressShotMasterPart: prevControlAccess.pressShotMasterPart || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    pressShotHistoryAct: prevControlAccess.pressShotHistoryAct || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    mtDbsDeptReq: prevControlAccess.mtDbsDeptReq || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    mtDbsMtReport: prevControlAccess.mtDbsMtReport || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    mtDbsReqWork: prevControlAccess.mtDbsReqWork || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    mtDbsDbAnl: prevControlAccess.mtDbsDbAnl || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    mtDbsSparePart: prevControlAccess.mtDbsSparePart || {
      view: false,
      create: false,
      update: false,
      delete: false,
    },
    schedule: prevControlAccess.schedule || {
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
            <strong>Master Data - Part</strong>
          </text>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.masterDataPart.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.masterDataPart.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.masterDataPart.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.masterDataPart.delete"
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
            <strong>Inventory Control - Part List</strong>
          </text>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.invControlPartList.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.invControlPartList.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.invControlPartList.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.invControlPartList.delete"
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
            <strong>Inventory Control - Master Part</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.invControlMasterPart.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.invControlMasterPart.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.invControlMasterPart.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.invControlMasterPart.delete"
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
            <strong>Inventory Control - Inbound</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.invControlInbound.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.invControlInbound.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.invControlInbound.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.invControlInbound.delete"
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
            <strong>Inventory Control - Outbound</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.invControlOutbound.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.invControlOutbound.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.invControlOutbound.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.invControlOutbound.delete"
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
            <strong>Press Shot - Part List</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.pressShotPartList.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.pressShotPartList.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.pressShotPartList.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.pressShotPartList.delete"
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
            <strong>Press Shot - Exchange Data</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.pressShotExcData.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.pressShotExcData.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.pressShotExcData.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.pressShotExcData.delete"
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
            <strong>Press Shot - Production Data</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.pressShotProdData.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.pressShotProdData.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.pressShotProdData.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.pressShotProdData.delete"
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
            <strong>Press Shot - Master Part</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.pressShotMasterPart.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.pressShotMasterPart.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.pressShotMasterPart.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.pressShotMasterPart.delete"
          />
        </VRow>
        <!-- --------------- -->
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
            <strong>Press Shot - History Activityt</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.pressShotHistoryAct.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.pressShotHistoryAct.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.pressShotHistoryAct.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.pressShotHistoryAct.delete"
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
            <strong>Maintenance DB System - Department Request </strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.mtDbsDeptReq.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.mtDbsDeptReq.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.mtDbsDeptReq.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.mtDbsDeptReq.delete"
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
            <strong>Maintenance DB System - Maintenance Report</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.mtDbsMtReport.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.mtDbsMtReport.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.mtDbsMtReport.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.mtDbsMtReport.delete"
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
            <strong>Maintenance DB System - Request to Workshop</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.mtDbsReqWork.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.mtDbsReqWork.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.mtDbsReqWork.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.mtDbsReqWork.delete"
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
            <strong
              >Maintenance DB System - Maintenance Data Analyzation</strong
            >
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.mtDbsDbAnl.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.mtDbsDbAnl.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.mtDbsDbAnl.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.mtDbsDbAnl.delete"
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
            <strong>Maintenance DB System - Spare Part Referring</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.mtDbsSparePart.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.mtDbsSparePart.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.mtDbsSparePart.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.mtDbsSparePart.delete"
          />
        </VRow>

        <VRow
          style="
            border-top: 1px solid #ccc;
            padding-top: 8px;
            padding-bottom: 16px;
            display: flex;
            align-items: center;
          "
        >
          <div class="pl-7" style="flex-grow: 2; flex-basis: 0">
            <strong>Schedule</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.schedule.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.schedule.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.schedule.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.schedule.delete"
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
