<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const isDetailDialogVisible = ref(false);

const selectedItem = ref("");
const searchQuery = ref("");
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "SPK & TGL ORDER",
    key: "spkNo",
  },
  {
    title: "PEMOHON",
    key: "pemohon",
  },
  {
    title: "JENIS PERBAIKAN",
    key: "jenisPerbaikan",
  },
  {
    title: "MESIN",
    key: "mesin",
  },
  {
    title: "JENIS PEKERJAAN",
    key: "jenisPekerjaan",
  },
  {
    title: "JUMLAH",
    key: "jumlah",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

// data table
const data = ref([]);

async function fetchData() {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests",
      {
        params: {
          search: searchQuery.value,
        },
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      }
    );

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deletePart() {
  try {
    const result = await $api(
      "/maintenance-database-system/department-requests/" + selectedItem.value,
      {
        method: "DELETE",

        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      }
    );

    selectedItem.value = "";
    isDeleteDialogVisible.value = false;
    toast.success("Delete success");
    fetchData();
  } catch (err) {
    toast.error("Failed to delete data");
    isDeleteDialogVisible.value = true;
    console.log(err);
  }
}

async function openEditPage(id) {
  await router.push({
    path: "/maintenance-database-system/department-request/add",
    query: { record_id: id },
  });
}

async function openDetailPage(id) {
  selectedItem.value = id;
  isDetailDialogVisible.value = true;
}

function openDeleteDialog(partCode) {
  selectedItem.value = partCode;
  isDeleteDialogVisible.value = true;
}

onMounted(() => {
  fetchData();
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
      ]"
    />
  </div>

  <!-- 👉 products -->
  <VCard class="mb-6">
    <VCardText class="d-flex flex-wrap gap-4">
      <div class="me-3 d-flex gap-3">
        <AppSelect
          :model-value="itemsPerPage"
          :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
            { value: -1, title: 'All' },
          ]"
          style="inline-size: 6.25rem"
          @update:model-value="itemsPerPage = parseInt($event, 10)"
        />
      </div>
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <!-- 👉 Search  -->
        <div style="inline-size: 15.625rem">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search"
            v-on:input="fetchData()"
          />
        </div>

        <!-- 👉 Export button -->
        <VBtn variant="tonal" color="secondary" prepend-icon="tabler-upload">
          Export
        </VBtn>

        <!-- 👉 Add button -->
        <VBtn prepend-icon="tabler-plus" to="department-request/add">
          Add Department Request
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <!-- 👉 Datatable  -->
    <VDataTable
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      :items="data"
      :headers="headers"
      class="text-no-wrap"
    >
      <!-- part name -->
      <template v-slot:header.spkNo="{ headers }">
        SPK NO &<br />TGL ORDER
      </template>
      <template #item.spkNo="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.RECORDID }}</span
            >
            <small>{{ item.ORDERDATETIME }}</small>
          </div>
        </div>
      </template>

      <!-- date -->
      <template #item.pemohon="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.ORDEREMPNAME }}</span
            >
            <small>Shop: {{ item.ORDERSHOP }}</small>
          </div>
        </div>
      </template>

      <!-- vendor -->
      <template v-slot:header.jenisPerbaikan="{ headers }">
        JENIS<br />PERBAIKAN
      </template>
      <template #item.jenisPerbaikan="{ item }">
        <div class="d-flex align-center">
          {{ item.MAINTENANCECODE }}
        </div>
      </template>

      <template #item.mesin="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.MACHINENAME }}</span
            >
            <small>{{ item.MACHINENO }}</small>
          </div>
        </div>
      </template>

      <template v-slot:header.jenisPekerjaan="{ headers }">
        JENIS<br />PEKERJAAN
      </template>
      <template #item.jenisPekerjaan="{ item }">
        <div class="d-flex align-center">
          {{ item.ORDERJOBTYPE }}
        </div>
      </template>

      <template #item.jumlah="{ item }">
        <div class="d-flex align-center">
          {{ item.ORDERQTTY }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openDetailPage(item.RECORDID)">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn @click="openEditPage(item.RECORDID)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn @click="openDeleteDialog(item.RECORDID)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <!-- 👉 Delete Dialog  -->
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

        <VBtn color="success" variant="elevated" @click="deletePart()">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>

  <DetailDepartmentRequestDialog
    v-model:isDialogVisible="isDetailDialogVisible"
    v-model:id="selectedItem"
  />
</template>
