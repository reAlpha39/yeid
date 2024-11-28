<script setup>
const data = ref();
const dataMachine = ref();

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
      "/maintenance-database-system/department-requests/" + encodeURIComponent(id)
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
        // errors.value = response._data.errors;
      },
    });

    dataMachine.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

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

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Detail Maintenance Request</h4>
      </VCardText>

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

      <VCard variant="outlined" style="background-color: #f9f9f9">
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
                  <VCol cols="8">
                    : {{ dataMachine?.machinename || "-" }}
                  </VCol>
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
                  <VCol cols="8">
                    : {{ dataMachine?.installdate || "-" }}
                  </VCol>
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
    </VCard>
  </VDialog>
</template>
