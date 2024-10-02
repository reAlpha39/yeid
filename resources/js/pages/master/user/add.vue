<script setup>
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();
const route = useRoute();

const roleAccesses = ["Operator", "Supervisor", "Manager"];

const departments = ref([]);

const form = ref();

const userId = ref();
const selectedDepartment = ref();
const selectedRoleAccess = ref();
const fullName = ref();
const email = ref();
const phone = ref();
const status = ref("Active");
const controlAccess = ref({
  machine: { view: false, create: false, update: false, delete: false },
  part: { view: false, create: false, update: false, delete: false },
  user: { view: false, create: false, update: false, delete: false },
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
    let controlAccessJson = JSON.stringify(controlAccess.value);

    if (isEdit.value) {
      const response = await $api("/master/users/" + userId.value, {
        method: "PUT",
        body: {
          FULLNAME: fullName.value,
          EMAIL: email.value,
          PHONE: phone.value,
          ROLEACCESS: convertRoleAccess(selectedRoleAccess.value),
          DEPARTMENT: selectedDepartment.value.DEPARTMENTCODE.trim(),
          STATUS: convertSwitch(status.value),
          CONTROLACCESS: controlAccessJson,
        },
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      toast.success("Edit user success");
    } else {
      const response = await $api("/master/users", {
        method: "POST",
        body: {
          FULLNAME: fullName.value,
          EMAIL: email.value,
          PHONE: phone.value,
          ROLEACCESS: convertRoleAccess(selectedRoleAccess.value),
          DEPARTMENT: selectedDepartment.value.DEPARTMENTCODE.trim(),
          STATUS: convertStatus(status.value),
          CONTROLACCESS: controlAccessJson,
        },
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      toast.success("Save machine success");
    }
    await router.push("/master/user");
  } catch (err) {
    toast.error("Failed to save user data");
    console.log(err);
  }
}

async function fetchDataEdit(id) {
  try {
    userId.value = id;
    const response = await $api("/master/users/" + id);
    console.log(response.data);
    prevData.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataDepartment(id) {
  try {
    if (id) {
      const response = await $api("/master/departments/" + id, {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      selectedDepartment.value = response.data;
      console.log(response.data);
      selectedDepartment.value.title =
        response.data.DEPARTMENTCODE + " | " + response.data.DEPARTMENTNAME;
    } else {
      const response = await $api("/master/departments", {
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      });

      departments.value = response.data;

      departments.value.forEach((maker) => {
        maker.title = maker.DEPARTMENTCODE + " | " + maker.DEPARTMENTNAME;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch department data");
    console.log(err);
  }
}

function convertStatus(category) {
  switch (category) {
    case "Active":
      return "1";
    case "Inactive":
      return "0";
    default:
      return "";
  }
}

function statusType(category) {
  switch (category) {
    case "0":
      return "Inactive";
    case "1":
      return "Active";
    default:
      return "";
  }
}

function convertRoleAccess(id) {
  switch (id) {
    case "Operator":
      return "O";
    case "Supervisor":
      return "S";
    case "Manager":
      return "M";
    default:
      return "";
  }
}

function roleAccessType(id) {
  switch (id) {
    case "O":
      return "Operator";
    case "S":
      return "Supervisor";
    case "M":
      return "Manager";
    default:
      return "";
  }
}

async function initEditData(id) {
  await fetchDataEdit(id);
  applyData();
}

async function applyData() {
  const data = prevData.value;

  await fetchDataDepartment(data.DEPARTMENT);

  fullName.value = data.FULLNAME;
  email.value = data.EMAIL;
  phone.value = data.PHONE;
  selectedRoleAccess.value = roleAccessType(data.ROLEACCESS);
  controlAccess.value = JSON.parse(data.CONTROLACCESS);
}

function convertSwitch(val) {
  if (val === "Active") {
    return "1";
  } else {
    return "0";
  }
}

onMounted(() => {
  fetchDataDepartment();

  const id = route.query.id;
  console.log("Fetching data for id:", id);
  if (id) {
    isEdit.value = true;
    initEditData(route.query.id);
  }
});
</script>

<template>
  <VForm ref="form" lazy-validation>
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
          title: 'Add New User',
          class: 'text-h4',
        },
      ]"
    />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="fullName"
              label="Full Name"
              :rules="[requiredValidator]"
              placeholder="Input full name"
              outlined
              maxlength="64"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="email"
              label="Email Address"
              :rules="[requiredValidator]"
              placeholder="Input email address"
              outlined
              maxlength="64"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="phone"
              label="Phone Number"
              :rules="[requiredValidator]"
              placeholder="Input phone number"
              outlined
              maxlength="14"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="6">
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
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="selectedRoleAccess"
              label="Role Access"
              :rules="[requiredValidator]"
              placeholder="Select role access"
              :items="roleAccesses"
              outlined
            />
          </VCol>
          <VCol cols="3">
            <VLabel style="color: #43404f; font-size: 13px">Status</VLabel>
            <VSwitch
              v-model="status"
              :rules="[requiredValidator]"
              :label="status"
              false-value="Inactive"
              true-value="Active"
            ></VSwitch>
          </VCol>
        </VRow>
      </VCardText>

      <VCard class="mx-6 mb-6" variant="outlined">
        <VCardTitle class="pb-3 pt-3 mb-3" style="background-color: #8692d014"
          >Fungsional</VCardTitle
        >

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
            <strong>Machine</strong>
          </text>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.machine.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.machine.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.machine.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.machine.delete"
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
            <strong>Part</strong>
          </div>

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
            <strong>User</strong>
          </div>

          <VCheckbox
            class="pr-2"
            label="View"
            v-model="controlAccess.user.view"
          />
          <VCheckbox
            class="pr-2"
            label="Create"
            v-model="controlAccess.user.create"
          />
          <VCheckbox
            class="pr-2"
            label="Update"
            v-model="controlAccess.user.update"
          />
          <VCheckbox
            class="pr-7"
            label="Delete"
            v-model="controlAccess.user.delete"
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
        <VBtn variant="outlined" color="error" to="/master/machine"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </VForm>
</template>

<!-- <template>
  <vx-card title="Functional Permissions">
    <vs-table :data="functionalItems">
      <template slot="thead">
        <vs-th>Item</vs-th>
        <vs-th>View</vs-th>
        <vs-th>Create</vs-th>
        <vs-th>Update</vs-th>
        <vs-th>Delete</vs-th>
      </template>

      <template slot-scope="{ data }">
        <vs-tr v-for="(item, index) in data" :key="index">
          <vs-td>{{ item.name }}</vs-td>
          <vs-td>
            <vs-checkbox v-model="item.permissions.view"></vs-checkbox>
          </vs-td>
          <vs-td>
            <vs-checkbox v-model="item.permissions.create"></vs-checkbox>
          </vs-td>
          <vs-td>
            <vs-checkbox v-model="item.permissions.update"></vs-checkbox>
          </vs-td>
          <vs-td>
            <vs-checkbox v-model="item.permissions.delete"></vs-checkbox>
          </vs-td>
        </vs-tr>
      </template>
    </vs-table>
  </vx-card>
</template>

<script>
export default {
  data() {
    return {
      functionalItems: [
        {
          name: "Machine",
          permissions: {
            view: false,
            create: false,
            update: false,
            delete: false,
          },
        },
        {
          name: "Other Item",
          permissions: {
            view: false,
            create: false,
            update: false,
            delete: false,
          },
        },
        // ... rest of functional items
      ],
    };
  },
};
</script> -->
