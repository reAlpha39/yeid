<script setup>
import moment from "moment";
import "moment/locale/id";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: ["create", "update"],
    subject: "mtDbsDeptReq",
  },
});

moment.locale("id");
const route = useRoute();
const toast = useToast();
const router = useRouter();

const form = ref();
const data = ref();
const dataMachine = ref();
const toBeApprove = ref(false);
const selectedEmployee = ref();
const note = ref();

const isDialogApproveVisible = ref(false);
const isDialogReviseVisible = ref(false);
const isDialogRejectVisible = ref(false);

const employees = ref([]);

async function fetchData(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(id),
      {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      }
    );

    data.value = response.data;
    fetchDataMachine(data.value.machineno);
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataMachine(id) {
  try {
    const response = await $api("/master/machines/" + encodeURIComponent(id), {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    dataMachine.value = response.data;
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataEmployee() {
  try {
    const response = await $api("/master/employees", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    employees.value = response.data;
    employees.value.forEach((data) => {
      data.title = data.employeename;
    });
  } catch (err) {
    console.log(err);
  }
}

async function approve() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    const id = route.query.record_id;

    await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(id) +
        "/approve",
      {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
        method: "POST",
        body: {
          note: note.value,
          employee_code: selectedEmployee.value
            ? selectedEmployee.value.employeecode
            : null,
        },
      }
    );

    toast.success("Request #" + id + " has been approved");
    await router.push("/maintenance-database-system/department-request");
  } catch (err) {
    toast.error(err);
    console.log(err);
  }
}

async function revise() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    const id = route.query.record_id;

    await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(id) +
        "/revise",
      {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
        method: "POST",
        body: {
          note: note.value,
        },
      }
    );

    toast.success("Request #" + id + " has been updated");
    await router.push("/maintenance-database-system/department-request");
  } catch (err) {
    console.log(err);
  }
}

async function reject() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    const id = route.query.record_id;

    await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(id) +
        "/reject",
      {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
        method: "POST",
        body: {
          note: note.value,
        },
      }
    );

    toast.success("Request #" + id + " has been rejected");
    await router.push("/maintenance-database-system/department-request");
  } catch (err) {
    console.log(err);
  }
}

function getRole(id) {
  if (id === "1") {
    return "Operator";
  }
  if (id === "2") {
    return "Supervisor";
  }
  if (id === "3") {
    return "Manager";
  }
}

const getStatusColor = (type) => {
  switch (type) {
    case "approved":
      return "success";
    case "revision":
      return "warning";
    default:
      return "error";
  }
};

const getStatusIcon = (type) => {
  switch (type) {
    case "approved":
      return "tabler-check";
    case "revision":
      return "tabler-hourglass";
    default:
      return "tabler-x";
  }
};

const getStatusText = (type) => {
  switch (type) {
    case "approved":
      return "Telah disetujui";
    case "revision":
      return "Revisi";
    default:
      return "Ditolak";
  }
};

onMounted(async () => {
  await fetchData(route.query.record_id);
  await fetchDataEmployee();
  toBeApprove.value = route.query.to_approve === "1" ? true : false;
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Maintenance Database System',
          class: 'text-h4',
        },
        {
          title: 'Department Request',
          class: 'text-h4',
        },
        {
          title: toBeApprove
            ? 'Persetujuan Department Request'
            : 'Detail Department Request',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VCard class="mb-6 pa-6">
    <VCard class="mb-6" variant="outlined" style="background-color: #f9f9f9">
      <VCardText>
        <span
          class="d-block font-weight-medium text-high-emphasis text-truncate"
          >SPK NO : {{ data?.recordid }}</span
        >
        <p>{{ data?.orderdatetime }}</p>
      </VCardText>

      <VRow class="pb-6">
        <VCol cols="6">
          <VCard variant="flat" class="ml-6 mr-2">
            <VCardText>
              <VRow>
                <VCol cols="4"> Jenis Perbaikan </VCol>
                <VCol cols="8"> : {{ data?.maintenancecode }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Pemohon </VCol>
                <VCol cols="8"> : {{ data?.orderempname }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Shop yang Dituju </VCol>
                <VCol cols="8"> : {{ data?.shopcode }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Mengapa dan Bagaimana </VCol>
                <VCol cols="8"> : {{ data?.ordertitle }} </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="6">
          <VCard variant="flat" class="mr-6 ml-2">
            <VCardText>
              <VRow>
                <VCol cols="4"> Jenis Pekerjaan </VCol>
                <VCol cols="8"> : {{ data?.orderjobtype }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Jumlah </VCol>
                <VCol cols="8"> : {{ data?.orderqtty }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Minta Tanggal Selesai </VCol>
                <VCol cols="8"> : {{ data?.orderfinishdate }} </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCard>

    <VCard class="mb-6" variant="outlined" style="background-color: #f9f9f9">
      <VCardText>
        <span
          class="d-block font-weight-medium text-high-emphasis text-truncate"
          >Informasi Machine</span
        >
      </VCardText>
      <VCard variant="flat" rounded="0">
        <VCardText>
          <VRow class="pb-6">
            <VCol cols="6">
              <VRow>
                <VCol cols="4"> Machine No </VCol>
                <VCol cols="8"> : {{ dataMachine?.machineno || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Machine Name </VCol>
                <VCol cols="8"> : {{ dataMachine?.machinename || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Model </VCol>
                <VCol cols="8"> : {{ dataMachine?.modelname || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Maker </VCol>
                <VCol cols="8"> : {{ dataMachine?.makername || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Shop </VCol>
                <VCol cols="8"> : {{ dataMachine?.shopname || "-" }} </VCol>
              </VRow>
            </VCol>
            <VCol cols="6">
              <VRow>
                <VCol cols="4"> Plant </VCol>
                <VCol cols="8"> : {{ dataMachine?.plantcode || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Tanggal Instalasi </VCol>
                <VCol cols="8"> : {{ dataMachine?.installdate || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> Line </VCol>
                <VCol cols="8"> : {{ dataMachine?.linecode || "-" }} </VCol>
              </VRow>
              <VRow>
                <VCol cols="4"> S/N </VCol>
                <VCol cols="8"> : {{ dataMachine?.serialno || "-" }} </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCard>

    <template v-if="data?.approval_record?.notes">
      <VCard
        v-for="(note, index) in data.approval_record.notes"
        :key="index"
        class="mb-6"
        variant="flat"
        style="background-color: #f9f9f9"
      >
        <VCardText>
          <VRow>
            <VCol cols="8">
              <div
                class="d-block font-weight-medium text-high-emphasis text-truncate"
              >
                {{ note.user.name }}
              </div>
              <div class="d-block text-truncate mb-4">
                {{ getRole(note.user.role_access) }}
                -
                {{ note.user.department.name }}
              </div>
              <div>
                {{ note.note }}
              </div>
            </VCol>
            <VCol cols="4" class="text-right">
              <div class="d-flex align-center justify-end">
                <VIcon
                  :color="getStatusColor(note.type)"
                  :icon="getStatusIcon(note.type)"
                  class="me-2"
                  size="small"
                />
                <span :class="`text-${getStatusColor(note.type)}`">
                  {{ getStatusText(note.type) }}
                </span>
              </div>
              <div
                class="text-medium-emphasis mt-2"
                style="font-size: 0.875rem"
              >
                {{
                  moment(note.created_at).format("dddd, D MMMM YYYY HH:mm:ss")
                }}
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </template>

    <VForm v-if="data?.can_approve" ref="form" lazy-validation>
      <VCard class="mb-6" variant="outlined" style="background-color: #f9f9f9">
        <VCardTitle>
          <AppAutocomplete
            v-if="data?.approval_record.pic === null"
            style="background-color: #ffffff"
            v-model="selectedEmployee"
            label="Penanggung Jawab"
            :rules="
              data?.approval_record.pic === null ? [requiredValidator] : []
            "
            placeholder="Pilih penanggung jawab"
            item-title="title"
            :items="employees"
            return-object
            outlined
          />

          <AppTextarea
            style="background-color: #ffffff"
            class="pt-4 pb-6"
            v-model="note"
            label="Catatan (opsional)"
            placeholder="Input catatan"
            outlined
            maxlength="512"
          />
        </VCardTitle>
      </VCard>

      <div class="d-flex justify-center py-4">
        <VBtn
          class="me-4"
          variant="outlined"
          color="error"
          to="/maintenance-database-system/department-request"
          >Cancel</VBtn
        >
        <VBtn
          color="error"
          class="me-4"
          @click="isDialogRejectVisible = !isDialogRejectVisible"
          >Reject</VBtn
        >
        <VBtn
          color="warning"
          class="me-4"
          @click="isDialogReviseVisible = !isDialogReviseVisible"
          >Revise</VBtn
        >
        <VBtn
          color="success"
          class="me-4"
          @click="isDialogApproveVisible = !isDialogApproveVisible"
          >Approve</VBtn
        >
      </div>
    </VForm>
  </VCard>

  <VDialog v-model="isDialogApproveVisible" max-width="500px">
    <VCard>
      <VCardTitle class="text-center">
        Are you sure you want to approve this request?
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="isDialogApproveVisible = !isDialogApproveVisible"
        >
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="approve"> OK </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="isDialogReviseVisible" max-width="500px">
    <VCard>
      <VCardTitle class="text-center">
        Are you sure you want to revise this request?
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="isDialogReviseVisible = !isDialogReviseVisible"
        >
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="revise"> OK </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="isDialogRejectVisible" max-width="500px">
    <VCard>
      <VCardTitle class="text-center">
        Are you sure you want to reject this request?
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="isDialogRejectVisible = !isDialogRejectVisible"
        >
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="reject"> OK </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>
