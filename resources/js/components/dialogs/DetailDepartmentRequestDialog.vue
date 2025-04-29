<script setup>
import moment from "moment";
import "moment/locale/id";

moment.locale("id");
const data = ref();
const dataMachine = ref();

const showSupervisorDeptList = ref(false);
const showManagerDeptList = ref(false);
const showSupervisorMtcList = ref(false);
const showManagerMtcList = ref(false);

const emit = defineEmits(["update:isDialogVisible", "submit"]);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  id: {
    required: false,
  },
});

async function fetchData(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(id)
    );

    data.value = response.data;
    checkApprovalUsers();
    fetchDataMachine(data.value.machineno);
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataMachine(id) {
  try {
    const response = await $api("/master/machines/" + encodeURIComponent(id), {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    dataMachine.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function checkApprovalUsers() {
  // console.log(data.value?.approval_record?.notes.length);
  if (data.value.approval_record?.notes.length === 0) {
    showManagerMtcList.value = true;
    showSupervisorMtcList.value = true;
    showManagerDeptList.value = true;
    showSupervisorDeptList.value = true;
    // console.log("aaaaaaa1");
  } else {
    var lastNote = data.value?.approval_record?.notes.at(-1);
    if (data.value?.approval_record?.approval_status === "revised") {
      showManagerMtcList.value = true;
      showSupervisorMtcList.value = true;
      showManagerDeptList.value = true;
      showSupervisorDeptList.value = true;
    } else if (lastNote.type === "revision" || lastNote.type === "rejected") {
      showManagerMtcList.value = false;
      showSupervisorMtcList.value = false;
      showManagerDeptList.value = false;
      showSupervisorDeptList.value = false;
    } else if (lastNote.is_user_dept_mtc && lastNote.user.role_access === "2") {
      showManagerMtcList.value = true;
      // console.log("aaaaaaa2");
    } else if (
      !lastNote.is_user_dept_mtc &&
      lastNote.user.role_access === "3"
    ) {
      showManagerMtcList.value = true;
      showSupervisorMtcList.value = true;
      // console.log("aaaaaaa3");
    } else if (
      !lastNote.is_user_dept_mtc &&
      lastNote.user.role_access === "2"
    ) {
      showManagerMtcList.value = true;
      showSupervisorMtcList.value = true;
      showManagerDeptList.value = true;
      // console.log("aaaaaaa4");
    } else {
      showManagerMtcList.value = false;
      showSupervisorMtcList.value = false;
      showManagerDeptList.value = false;
      showSupervisorDeptList.value = false;
      // console.log("aaaaaaa5");
    }
    // console.log(showManagerMtcList.value);
    // console.log(showSupervisorMtcList.value);
    // console.log(showManagerDeptList.value);
    // console.log(showSupervisorDeptList.value);
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
    case "rejected":
      return "error";
    default:
      return "black";
  }
};

const getStatusIcon = (type) => {
  switch (type) {
    case "approved":
      return "tabler-check";
    case "revision":
      return "tabler-hourglass";
    case "rejected":
      return "tabler-x";
    default:
      return "tabler-hourglass";
  }
};

const getStatusText = (type) => {
  switch (type) {
    case "approved":
      return "Telah disetujui";
    case "revision":
      return "Revisi";
    case "rejected":
      return "Ditolak";
    default:
      return "Menunggu";
  }
};

const getMaintenanceType = (type) => {
  for (var maintenanceType of maintenanceTypes) {
    if (maintenanceType.split("|")[0] === type) {
      return maintenanceType;
    }
  }

  return type;
};

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const handleItemClick = (item) => {
  emit("update:isDialogVisible", false);
};

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      fetchData(props.id);
      console.log("Dialog opened with id:", props.id);
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
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard>
      <!-- Container div with max-height and overflow-y-auto -->
      <div class="dialog-content overflow-y-auto" style="max-height: 80vh">
        <div class="pa-2 pa-sm-10">
          <VCardText>
            <h4 class="text-h4 text-center mb-2">Detail Maintenance Request</h4>
          </VCardText>

          <VCard
            class="mb-6"
            variant="outlined"
            style="background-color: #f9f9f9"
          >
            <VCardText>
              <h3
                class="d-block font-weight-medium text-high-emphasis text-truncate"
              >
                SPK NO : {{ data?.recordid }}
              </h3>
              <small>{{ data?.orderdatetime }}</small>
            </VCardText>

            <VRow class="pb-6">
              <VCol cols="6">
                <VCard variant="flat" class="ml-6 mr-2">
                  <VCardText>
                    <VRow>
                      <VCol cols="4"> Jenis Perbaikan </VCol>
                      <VCol cols="8">
                        : {{ getMaintenanceType(data?.maintenancecode) }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Pemohon </VCol>
                      <VCol cols="8"> : {{ data?.orderempname }} </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Order Shop Code </VCol>
                      <VCol cols="8"> : {{ data?.ordershop ?? "-" }} </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Order Shop Name</VCol>
                      <VCol cols="8">
                        : {{ data?.shop?.shopname ?? "-" }}
                      </VCol>
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

          <VCard
            class="mb-6"
            variant="outlined"
            style="background-color: #f9f9f9"
          >
            <VCardText>
              <h3
                class="d-block font-weight-medium text-high-emphasis text-truncate"
              >
                Informasi Machine
              </h3>
            </VCardText>
            <VCard variant="flat" rounded="0">
              <VCardText>
                <VRow class="pb-6">
                  <VCol cols="6">
                    <VRow>
                      <VCol cols="4"> Machine No </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.machineno || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Machine Name </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.machinename || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Model </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.modelname || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Maker </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.makername || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Shop Code </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.shopcode || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Shop Name </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.shopname || "-" }}
                      </VCol>
                    </VRow>
                  </VCol>
                  <VCol cols="6">
                    <VRow>
                      <VCol cols="4"> Plant </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.plantcode || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Tanggal Instalasi </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.installdate || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> Line </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.linecode || "-" }}
                      </VCol>
                    </VRow>
                    <VRow>
                      <VCol cols="4"> S/N </VCol>
                      <VCol cols="8">
                        : {{ dataMachine?.serialno || "-" }}
                      </VCol>
                    </VRow>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCard>

          <VCard class="mb-6" variant="outlined">
            <VCardText class="d-flex flex-wrap gap-4">
              <div>
                <h3 class="font-weight-medium text-high-emphasis mt-2">
                  Penanggung Jawab Pengerjaan
                </h3>
              </div>

              <VSpacer />

              <div>
                <VCard
                  variant="flat"
                  style="background-color: #f9f9f9"
                  class="pa-2"
                >
                  <small>{{
                    data?.approval_record?.pic?.employeename ?? "-"
                  }}</small>
                </VCard>
              </div>
            </VCardText>
          </VCard>

          <VCard class="mb-6" variant="outlined">
            <VCardText>
              <h3
                class="d-block font-weight-medium text-high-emphasis text-truncate mb-4"
              >
                Approval
              </h3>

              <div class="d-block text-truncate mb-2">Created By</div>
              <VRow>
                <VCol cols="8">
                  <div
                    class="d-block font-weight-medium text-high-emphasis text-truncate"
                  >
                    {{ data?.approval_record?.created_by.name }}
                  </div>
                  <small class="d-block">
                    {{ getRole(data?.approval_record?.created_by.role_access) }}
                    -
                    {{ data?.approval_record?.department.name }}
                  </small>
                </VCol>
                <VCol cols="4" class="text-right">
                  <div
                    class="text-medium-emphasis mt-2"
                    style="font-size: 0.875rem"
                  >
                    {{
                      moment(data?.approval_record?.created_at).format(
                        "dddd, D MMMM YYYY HH:mm:ss"
                      )
                    }}
                  </div>
                </VCol>
              </VRow>
            </VCardText>

            <template v-if="data?.approval_record?.notes">
              <VCard
                v-for="(note, index) in data.approval_record.notes"
                :key="index"
                variant="outlined"
                class="mb-4 mx-6"
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
                      <small class="d-block text-truncate mb-4">
                        {{ getRole(note.user.role_access) }}
                        -
                        {{ note.user.department.name }}
                      </small>
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
                          moment(note.created_at).format(
                            "dddd, D MMMM YYYY HH:mm:ss"
                          )
                        }}
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </template>

            <!--  -->
            <template v-if="showSupervisorDeptList">
              <VCard
                v-for="(user, index) in data.supervisor_department"
                :key="index"
                variant="outlined"
                class="mb-4 mx-6"
                style="background-color: #f9f9f9"
              >
                <VCardText>
                  <VRow>
                    <VCol cols="8">
                      <div
                        class="d-block font-weight-medium text-high-emphasis text-truncate"
                      >
                        {{ user.name }}
                      </div>
                      <small class="d-block text-truncate mb-4">
                        {{ getRole(user.role_access) }}
                        -
                        {{ user.department.name }}
                      </small>
                    </VCol>
                    <VCol cols="4" class="text-right">
                      <div class="d-flex align-center justify-end">
                        <VIcon
                          :color="getStatusColor()"
                          :icon="getStatusIcon()"
                          class="me-2"
                          size="small"
                        />
                        <span :class="`text-${getStatusColor()}`">
                          {{ getStatusText() }}
                        </span>
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </template>

            <template v-if="showManagerDeptList">
              <VCard
                v-for="(user, index) in data.manager_department"
                :key="index"
                variant="outlined"
                class="mb-4 mx-6"
                style="background-color: #f9f9f9"
              >
                <VCardText>
                  <VRow>
                    <VCol cols="8">
                      <div
                        class="d-block font-weight-medium text-high-emphasis text-truncate"
                      >
                        {{ user.name }}
                      </div>
                      <small class="d-block text-truncate mb-4">
                        {{ getRole(user.role_access) }}
                        -
                        {{ user.department.name }}
                      </small>
                    </VCol>
                    <VCol cols="4" class="text-right">
                      <div class="d-flex align-center justify-end">
                        <VIcon
                          :color="getStatusColor()"
                          :icon="getStatusIcon()"
                          class="me-2"
                          size="small"
                        />
                        <span :class="`text-${getStatusColor()}`">
                          {{ getStatusText() }}
                        </span>
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </template>

            <template v-if="showSupervisorMtcList">
              <VCard
                v-for="(user, index) in data.supervisor_mtc"
                :key="index"
                variant="outlined"
                class="mb-4 mx-6"
                style="background-color: #f9f9f9"
              >
                <VCardText>
                  <VRow>
                    <VCol cols="8">
                      <div
                        class="d-block font-weight-medium text-high-emphasis text-truncate"
                      >
                        {{ user.name }}
                      </div>
                      <small class="d-block text-truncate mb-4">
                        {{ getRole(user.role_access) }}
                        -
                        {{ user.department.name }}
                      </small>
                    </VCol>
                    <VCol cols="4" class="text-right">
                      <div class="d-flex align-center justify-end">
                        <VIcon
                          :color="getStatusColor()"
                          :icon="getStatusIcon()"
                          class="me-2"
                          size="small"
                        />
                        <span :class="`text-${getStatusColor()}`">
                          {{ getStatusText() }}
                        </span>
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </template>

            <template v-if="showManagerMtcList">
              <VCard
                v-for="(user, index) in data.manager_mtc"
                :key="index"
                variant="outlined"
                class="mb-4 mx-6"
                style="background-color: #f9f9f9"
              >
                <VCardText>
                  <VRow>
                    <VCol cols="8">
                      <div
                        class="d-block font-weight-medium text-high-emphasis text-truncate"
                      >
                        {{ user.name }}
                      </div>
                      <small class="d-block text-truncate mb-4">
                        {{ getRole(user.role_access) }}
                        -
                        {{ user.department.name }}
                      </small>
                    </VCol>
                    <VCol cols="4" class="text-right">
                      <div class="d-flex align-center justify-end">
                        <VIcon
                          :color="getStatusColor()"
                          :icon="getStatusIcon()"
                          class="me-2"
                          size="small"
                        />
                        <span :class="`text-${getStatusColor()}`">
                          {{ getStatusText() }}
                        </span>
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </template>
          </VCard>
        </div>
      </div>
    </VCard>
  </VDialog>
</template>

<style scoped>
.dialog-content {
  /* Smooth scrolling for modern browsers */
  scroll-behavior: smooth;
  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none;
  scrollbar-width: thin;
}

/* Custom scrollbar styling for webkit browsers */
.dialog-content::-webkit-scrollbar {
  width: 8px;
}

.dialog-content::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.dialog-content::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.dialog-content::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
