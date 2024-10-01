<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);

const selectedItem = ref("");
const searchQuery = ref("");
// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

// headers
const headers = [
  {
    title: "MACHINE NO",
    key: "MACHINENO",
  },
  {
    title: "MACHINE NAME",
    key: "MACHINENAME",
  },
  {
    title: "STATUS",
    key: "STATUS",
  },
  {
    title: "PLANT",
    key: "PLANTCODE",
  },
  {
    title: "SHOP",
    key: "SHOPCODE",
  },
  {
    title: "LINE",
    key: "LINECODE",
  },
  {
    title: "MODEL",
    key: "MODELNAME",
  },
  {
    title: "MAKER",
    key: "MAKERNAME",
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
    const response = await $api("/master/machines", {
      params: {
        search: searchQuery.value,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deletePart() {
  try {
    const result = await $api("/master/machines/" + selectedItem.value, {
      method: "DELETE",

      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

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
    path: "/master/machine/add",
    query: { machine_no: id },
  });
}

function openDeleteDialog(partCode) {
  selectedItem.value = partCode;
  isDeleteDialogVisible.value = true;
}

function statusType(category) {
  switch (category) {
    case "A":
      return "Active";
    case "D":
      return "Disposed";
    case "R":
      return "Resting";
    case "T":
      return "Transfered";
    default:
      return "";
  }
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
          title: 'Master',
          class: 'text-h4',
        },
        {
          title: 'Machine',
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
        <VBtn prepend-icon="tabler-plus" to="machine/add">
          Add New Machine
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
      <template #item.machineno="{ item }">
        <div class="d-flex align-center">
          <span
            class="d-block font-weight-medium text-high-emphasis text-truncate"
            >{{ item.machineno }}</span
          >
        </div>
      </template>

      <!-- date -->
      <template #item.machinename="{ item }">
        <div class="d-flex align-center">
          {{ item.machinename }}
        </div>
      </template>

      <!-- vendor -->
      <template #item.STATUS="{ item }">
        <div class="d-flex align-center">
          {{ statusType(item.STATUS) }}
        </div>
      </template>

      <template #item.plantcode="{ item }">
        <div class="d-flex align-center">
          {{ item.plantcode }}
        </div>
      </template>

      <template #item.shopcode="{ item }">
        <div class="d-flex align-center">
          {{ item.shopcode }}
        </div>
      </template>

      <template #item.linecode="{ item }">
        <div class="d-flex align-center">
          {{ item.linecode }}
        </div>
      </template>

      <template #item.modelname="{ item }">
        <div class="d-flex align-center">
          {{ item.modelname }}
        </div>
      </template>

      <template #item.makername="{ item }">
        <div class="d-flex align-center">
          {{ item.makername }}
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openEditPage(item.MACHINENO)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn @click="openDeleteDialog(item.MACHINENO)">
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
</template>
