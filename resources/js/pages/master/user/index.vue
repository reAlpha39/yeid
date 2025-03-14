<script setup>
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { VCardText } from "vuetify/lib/components/index.mjs";

definePage({
  meta: {
    action: "view",
    subject: "user",
  },
});

const toast = useToast();
const router = useRouter();

const isDeleteDialogVisible = ref(false);
const menu = ref(false);

const selectedItem = ref("");
const searchQuery = ref("");
const selectedDepartment = ref();
const selectedRoleAccess = ref();
const selectedStatus = ref();

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

const departments = ref([]);
const roleAccesses = ["1", "2", "3"];
const statuses = ["Active", "Inactive"];

// headers
const headers = [
  {
    title: "FULL NAME",
    key: "name",
  },
  {
    title: "NPK",
    key: "nik",
  },
  {
    title: "EMAIL ADDRESS",
    key: "email",
  },
  {
    title: "DEPARTMENT",
    key: "department_name",
  },
  {
    title: "ROLE ACCESS",
    key: "role_access",
  },
  {
    title: "STATUS",
    key: "status",
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
    const response = await $api("/master/users", {
      params: {
        search: searchQuery.value,
        department: selectedDepartment.value?.id,
        status: convertStatus(selectedStatus.value),
        roleAccess: convertRoleAccess(selectedRoleAccess.value),
      },
      // onResponseError({ response }) {
      //   errors.value = response._data.errors;
      // },
    });

    data.value = response.data;

    data.value = response.data.map((item) => ({
      ...item,
      menu: false,
    }));
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function deletePart() {
  try {
    const result = await $api(
      "/master/users/" + encodeURIComponent(selectedItem.value),
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

async function updateStatus(id, status) {
  try {
    const response = await $api(
      "/master/users/" + encodeURIComponent(id) + "/status",
      {
        method: "PUT",
        body: {
          status: status,
        },
        onResponseError({ response }) {
          errors.value = response._data.errors;
        },
      }
    );

    menu.value = false;
    toast.success("Update status success");
    fetchData();
  } catch (err) {
    toast.error("Failed to update status");
    console.log(err);
  }
}

async function openEditPage(id) {
  await router.push({
    path: "/master/user/add",
    query: { id: id },
  });
}

async function fetchDataDepartment() {
  try {
    const response = await $api("/master/departments", {
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    departments.value = response.data;

    departments.value.forEach((maker) => {
      maker.title = maker.code + " | " + maker.name;
    });
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

function convertRoleAccess(id) {
  switch (id) {
    case "1":
      return "1";
    case "2":
      return "2";
    case "3":
      return "3";
    default:
      return "";
  }
}

function openDeleteDialog(partCode) {
  selectedItem.value = partCode;
  isDeleteDialogVisible.value = true;
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

const loadingExport = ref(false);

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get("/api/master/users/export", {
      responseType: "blob",
      params: {
        search: searchQuery.value,
        department: selectedDepartment.value?.id,
        status: convertStatus(selectedStatus.value),
        roleAccess: convertRoleAccess(selectedRoleAccess.value),
      },
      headers: accessToken
        ? {
            Authorization: `Bearer ${accessToken}`,
          }
        : {},
    });

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "users.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

const debouncedFetchData = debounce(fetchData, 500);

watch(searchQuery, () => {
  debouncedFetchData();
});

onMounted(() => {
  fetchData();
  fetchDataDepartment();
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
          title: 'User',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <!-- 👉 products -->
  <VCard class="mb-6">
    <VCardText>
      <VRow>
        <VCol cols="3">
          <VAutocomplete
            v-model="selectedDepartment"
            placeholder="Select department"
            item-title="title"
            :items="departments"
            return-object
            outlined
            clearable
            @update:modelValue="fetchData()"
          />
        </VCol>
        <VCol cols="3">
          <VSelect
            v-model="selectedRoleAccess"
            placeholder="Select role access"
            :items="roleAccesses"
            outlined
            clearable
            @update:modelValue="fetchData()"
          />
        </VCol>
        <VCol cols="3">
          <VSelect
            v-model="selectedStatus"
            placeholder="Select status"
            :items="statuses"
            outlined
            clearable
            @update:modelValue="fetchData()"
          />
        </VCol>
      </VRow>
    </VCardText>
    <VCardText class="d-flex flex-wrap gap-4">
      <!-- <div class="me-3 d-flex gap-3">
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
      </div> -->
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <!-- 👉 Search  -->
        <div style="inline-size: 15.625rem">
          <AppTextField v-model="searchQuery" placeholder="Search" />
        </div>

        <!-- 👉 Export button -->
        <VBtn
          variant="tonal"
          prepend-icon="tabler-upload"
          @click="handleExport"
          :loading="loadingExport"
        >
          Export
        </VBtn>

        <!-- 👉 Add button -->
        <VBtn
          v-if="$can('create', 'user')"
          prepend-icon="tabler-plus"
          to="user/add"
        >
          Add New User
        </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <!-- 👉 Datatable  -->
    <div class="sticky-actions-wrapper">
      <VDataTable
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="data"
        :headers="headers"
        :sort-by="[{ key: 'name', order: 'asc' }]"
        class="text-no-wrap"
        height="562"
      >
        <!-- part name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.name }}</span
            >
          </div>
        </template>

        <template #item.status="{ item }">
          <div class="d-flex align-center">
            {{ statusType(item.status) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="align-center">
            <IconBtn
              v-if="$can('update', 'user')"
              @click="openEditPage(item.id)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              v-if="$can('delete', 'user')"
              @click="openDeleteDialog(item.id)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
            <VMenu v-model="item.menu">
              <!-- Each row has its own menu state -->
              <template #activator="{ props }">
                <IconBtn v-if="$can('update', 'user')" v-bind="props">
                  <VIcon icon="tabler-dots-vertical" />
                </IconBtn>
              </template>

              <VCard>
                <VList>
                  <VListItem
                    @click="
                      updateStatus(item.id, item.status === '0' ? '1' : '0')
                    "
                  >
                    <span>{{
                      item.status === "0" ? "Activate" : "Deactivate"
                    }}</span>
                  </VListItem>
                </VList>
              </VCard>
            </VMenu>
          </div>
        </template>
      </VDataTable>
    </div>
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
