<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "maintenanceReport",
  },
});

const toast = useToast();
const router = useRouter();
const route = useRoute();

const isAddEmployeeDialogVisible = ref(false);
const selectedEmployee = ref();
const form = ref(null);
const pemohons = ref([]);
const shops = ref([]);

const title = ref();
const requestDate = ref(null);
const selectedPemohon = ref();
const orderName = ref();
const selectedShop = ref();
const reason = ref();
const reqFinishDate = ref();
const deliveryPlace = ref();
const categoryRadio = ref("1");
const note = ref();
const employees = ref([]);
const statusRadio = ref("R");
const finishDate = ref(null);
const inspector = ref(null);

const prevData = ref();
const isEdit = ref(false);
const isLoadingEditData = ref(false);

async function addData() {
  try {
    const { valid, errors } = await form.value?.validate();
    if (valid === false) {
      return;
    }

    const requestData = {
      requestdate: requestDate.value,
      employeecode: selectedPemohon.value.employeecode,
      employeename: selectedPemohon.value.employeename,
      shopcode: selectedShop.value.shopcode,
      shopname: selectedShop.value.shopname,
      title: title.value,
      reason: reason.value,
      reqfinishdate: reqFinishDate.value,
      asapflag: categoryRadio.value,
      deliveryplace: deliveryPlace.value,
      note: note.value,
      finishdate: finishDate.value,
      inspector: inspector.value,
      ordername: orderName.value,
      status: statusRadio.value,
      staffnames: formatStaffNames(),
    };

    if (isEdit.value) {
      const response = await $api(
        "/maintenance-database-system/request-workshop/" +
          encodeURIComponent(route.query.wsrid),
        {
          method: "PUT",
          body: requestData,
          onResponseError({ response }) {
            errors.value = response._data.errors;
          },
        }
      );
    } else {
      const response = await $api(
        "/maintenance-database-system/request-workshop",
        {
          method: "POST",
          body: requestData,
          onResponseError({ response }) {
            errors.value = response._data.errors;
          },
        }
      );
    }

    toast.success("Data saved successfully");
    await router.push("/maintenance-database-system/request-to-workshop");
  } catch (err) {
    toast.error("Failed to save data");
    console.log(err);
  }
}

function formatStaffNames() {
  return employees.value
    .map((employee) => `${employee.employeecode}|${employee.employeename}`)
    .join("\t");
}

async function fetchDataEmployee(id, isSelectPemohon) {
  try {
    if (id) {
      const response = await $api(
        "/master/employees/" + encodeURIComponent(id)
      );

      let data = response.data;

      if (isSelectPemohon) {
        selectedPemohon.value = data;
        selectedPemohon.value.title = data.employeename;
      }
    } else {
      const response = await $api("/master/employees");

      pemohons.value = response.data;
      pemohons.value.forEach((data) => {
        data.title = data.employeename;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataShop(id) {
  try {
    if (id) {
      const response = await $api("/master/shops/" + encodeURIComponent(id));

      selectedShop.value = response.data;
      selectedShop.value.title =
        response.data.shopcode + " | " + response.data.shopname;
    } else {
      const response = await $api("/master/shops");

      shops.value = response.data;

      shops.value.forEach((data) => {
        data.title = data.shopcode + " | " + data.shopname;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function handleAddEmployee() {
  selectedEmployee.value = null;
  isAddEmployeeDialogVisible.value = true;
}

function handleDeleteEmployee(id) {
  employees.value = employees.value.filter((item) => item.employeecode !== id);
}

function handleMachinesSelected(items) {
  employees.value = items;
}

async function fetchDataEdit(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/request-workshop/" + encodeURIComponent(id)
    );
    prevData.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function initEditData(id) {
  isLoadingEditData.value = true;
  try {
    await fetchDataEdit(id);

    const data = prevData.value;
    await fetchDataEmployee(data.employeecode, true);
    await fetchDataShop(data.shopcode);

    title.value = data.title;
    requestDate.value = data.requestdate;
    orderName.value = data.ordername;
    reason.value = data.reason;
    reqFinishDate.value = data.reqfinishdate;
    deliveryPlace.value = data.deliveryplace;
    categoryRadio.value = data.asapflag;
    note.value = data.note;
    statusRadio.value = data.status;
    finishDate.value = data.finishdate;
    inspector.value = data.inspector;

    revertStaffNames(data.staffnames);
  } finally {
    isLoadingEditData.value = false;
  }
}

async function revertStaffNames(data) {
  const employeeEntries = data.split("\t");

  employees.value = [];

  for (const entry of employeeEntries) {
    const [employeecode, employeename] = entry.split("|");
    const employeeData = await fetchDataEmployee(employeecode);
    if (employeeData) {
      employees.value.push(employeeData);
    } else {
      employees.value.push({ employeecode, employeename });
    }
  }
}

onMounted(() => {
  // fetchData();
  fetchDataShop();
  fetchDataEmployee();

  const id = route.query.wsrid;
  console.log("Fetching data for wsrid:", id);
  if (id) {
    isEdit.value = true;
    initEditData(id);
  }
});
</script>

<template>
  <VBreadcrumbs
    class="px-0 pb-2 pt-0"
    :items="[
      {
        title: 'Maintenance Database System',
        class: 'text-h4',
      },
      {
        title: 'Request to Workshop',
        class: 'text-h4',
      },
      {
        title: isEdit ? 'Update Request' : 'Add Request',
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
    <VCard class="mb-6 px-6 py-4">
      <VRow>
        <VCol cols="3">
          <AppTextField
            v-model="title"
            label="Request Title"
            :rules="[requiredValidator]"
            placeholder="Input title"
            outlined
            maxlength="50"
          />
        </VCol>
        <VCol cols="3">
          <AppDateTimePicker
            v-model="requestDate"
            :rules="[requiredValidator]"
            label="Request Date"
            placeholder="20240101"
            :config="{ enableTime: false, dateFormat: 'Ymd' }"
            append-inner-icon="tabler-calendar"
          />
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="3">
          <AppTextField
            v-model="orderName"
            label="Order Name"
            :rules="[requiredValidator]"
            placeholder="Input order name"
            outlined
            maxlength="50"
          />
        </VCol>

        <VCol cols="3">
          <AppAutocomplete
            v-model="selectedPemohon"
            label="Pemohon"
            :rules="[requiredValidator]"
            placeholder="Pilih pemohon"
            item-title="title"
            :items="pemohons"
            return-object
            outlined
          />
        </VCol>
        <VCol cols="6">
          <AppAutocomplete
            v-model="selectedShop"
            label="Shop yang Dituju"
            :rules="[requiredValidator]"
            placeholder="Pilih shop"
            item-title="title"
            :items="shops"
            return-object
            outlined
          />
        </VCol>
      </VRow>

      <AppTextarea
        class="my-6"
        label="Reason"
        placeholder="Input alasan"
        v-model="reason"
        :rules="[requiredValidator]"
        outlined
        maxlength="50"
      />

      <VRow>
        <VCol cols="3">
          <AppTextField
            v-model="reqFinishDate"
            label="Req. Finish Date"
            :rules="[requiredValidator]"
            placeholder="Input request finish date"
            outlined
            maxlength="50"
          />
        </VCol>
        <VCol cols="3">
          <AppTextField
            v-model="deliveryPlace"
            label="Delivery Place"
            :rules="[requiredValidator]"
            placeholder="Input delivery place"
            outlined
            maxlength="50"
          />
        </VCol>
        <VCol>
          <VLabel style="color: #43404f; font-size: 13px">Category</VLabel>
          <VRadioGroup v-model="categoryRadio" inline>
            <VRadio label="Jig/Mold/Dies" value="1" />
            <VRadio label="WorkShop" value="2" />
            <VRadio label="Facility" value="3" />
          </VRadioGroup>
        </VCol>
      </VRow>

      <AppTextarea
        class="my-6"
        label="Note"
        placeholder="Input catatn"
        v-model="note"
        :rules="[requiredValidator]"
        outlined
        maxlength="50"
      />
    </VCard>

    <VCard class="mb-6">
      <VRow>
        <VCol cols="10">
          <VCardTitle class="mt-3 ml-2">Staff Name</VCardTitle>
          <VCardText>
            Silahkan tambah data staff. Pastikan memilih staff yang sesuai
            dengan data yang valid.
          </VCardText>
        </VCol>
        <VCol cols="2" class="d-flex justify-end">
          <VBtn
            class="ma-4"
            prepend-icon="tabler-plus"
            @click="handleAddEmployee()"
          >
            Tambah
          </VBtn>
        </VCol>
      </VRow>

      <VCard variant="outlined" class="mx-4">
        <VCardText
          v-if="employees.length === 0"
          class="my-4 justify-center"
          style="text-align: center"
        >
          Data parts masih kosong. Silakan tambah parts yang ganti.
        </VCardText>
        <div v-else style="overflow-x: auto">
          <VTable class="text-no-wrap" height="250">
            <thead>
              <tr>
                <th>STAFF CODE</th>
                <th>STAFF NAME</th>
                <th class="actions-column">ACTIONS</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in employees" :key="item.employeecode">
                <td>{{ item.employeecode }}</td>
                <td>{{ item.employeename }}</td>
                <td class="actions-column">
                  <div class="action-buttons">
                    <IconBtn>
                      <VIcon
                        @click="handleDeleteEmployee(item.employeecode)"
                        icon="tabler-trash"
                      />
                    </IconBtn>
                  </div>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCard>

      <br />
    </VCard>

    <VCard v-if="isEdit" class="mb-6 px-2 py-2">
      <VCardTitle class="mt-3 ml-2">Status</VCardTitle>

      <VRadioGroup v-model="statusRadio" inline>
        <VRow class="mx-2 my-2">
          <VCol>
            <VCard
              class="px-2 pt-3 pb-2"
              variant="outlined"
              :class="{ 'active-card': statusRadio === 'R' }"
            >
              <VRadio label="Requesting" value="R" />
            </VCard>
          </VCol>
          <VCol>
            <VCard
              class="px-2 pt-3 pb-2"
              variant="outlined"
              :class="{ 'active-card': statusRadio === 'F' }"
            >
              <VRadio label="Finished" value="F" />
            </VCard>
          </VCol>
          <VCol>
            <VCard
              class="px-2 pt-3 pb-2"
              variant="outlined"
              :class="{ 'active-card': statusRadio === 'C' }"
            >
              <VRadio label="Cancelled" value="C" />
            </VCard>
          </VCol>
        </VRow>
      </VRadioGroup>

      <VCard class="mx-4 mb-6" variant="flat" style="background-color: #f9f9f9">
        <VRow class="mx-4 my-2">
          <VCol cols="4">
            <AppDateTimePicker
              v-model="finishDate"
              :rules="statusRadio === 'F' ? [requiredValidator] : []"
              label="Finish Date"
              placeholder="20240101"
              :config="{ enableTime: false, dateFormat: 'Ymd' }"
              append-inner-icon="tabler-calendar"
              style="background-color: #ffffff"
            />
          </VCol>
          <VCol cols="8">
            <AppTextField
              v-model="inspector"
              label="Inspector"
              placeholder="Input inspector"
              outlined
              maxlength="50"
              style="background-color: #ffffff"
            />
          </VCol>
        </VRow>
      </VCard>
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addData">Save</VBtn>
        <VBtn
          variant="outlined"
          color="error"
          to="/maintenance-database-system/request-to-workshop"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </VForm>

  <SelectEmployeesDialog
    v-model:isDialogVisible="isAddEmployeeDialogVisible"
    v-model:items="employees"
    @submit="handleMachinesSelected"
  />
</template>

<style scoped>
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
  border-color: #7267e8 !important;
}
</style>
