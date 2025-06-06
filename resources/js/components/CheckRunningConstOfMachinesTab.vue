<script setup>
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const now = new Date();
const currentYear = now.getFullYear();
const currentMonth = now.getMonth() + 1;

const isLoading = ref(false);
const loadingExport = ref(false);
const isSelectMachineDialogVisible = ref(false);

const years = ref([]);
const data = ref([]);
const shops = ref([]);
const months = [
  { value: 1, label: "Januari" },
  { value: 2, label: "Februari" },
  { value: 3, label: "Maret" },
  { value: 4, label: "April" },
  { value: 5, label: "Mei" },
  { value: 6, label: "Juni" },
  { value: 7, label: "Juli" },
  { value: 8, label: "Agustus" },
  { value: 9, label: "September" },
  { value: 10, label: "Oktober" },
  { value: 11, label: "November" },
  { value: 12, label: "Desember" },
];

const year = ref(currentYear);
const month = ref(null);
const plantCode = ref();
const shop = ref();
const machine = ref();

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}
async function fetchData() {
  try {
    isLoading.value = true;

    const response = await $api(
      "/maintenance-database-system/spare-part-referring/machines-cost",
      {
        params: {
          year: year.value.toString(),
          month: month.value?.value.toString(),
          plant_code: plantCode.value,
          shop_code: shop.value?.shopcode,
          machine_no: machine.value?.machineno,
        },
      }
    );

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data machines cost");
    console.log(err);
  } finally {
    isLoading.value = false;
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops");

    shops.value = response.data;

    shops.value.forEach((data) => {
      data.title = data.shopcode + " | " + data.shopname;
    });
  } catch (err) {
    toast.error("Failed to fetch data shop");
    console.log(err);
  }
}

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get(
      "/api/maintenance-database-system/spare-part-referring/machines-cost/export",
      {
        responseType: "blob",
        headers: accessToken
          ? {
              Authorization: `Bearer ${accessToken}`,
            }
          : {},
        params: {
          year: year.value.toString(),
          month: month.value?.value.toString(),
          plant_code: plantCode.value,
          shop_code: shop.value?.shopcode,
          machine_no: machine.value?.machineno,
        },
      }
    );

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "machines_cost.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

function handleMachinesSelected(item) {
  item.title = item.machineno + " | " + item.machinename;
  machine.value = item;
}

const debouncedFetchData = debounce(fetchData, 500);

watch([month, shop, machine, plantCode], () => {
  debouncedFetchData();
});

onMounted(() => {
  getLastTenYears();
  fetchData();
  fetchDataShop();
});
</script>

<template>
  <VCard class="mt-5">
    <VCardTitle class="d-flex flex-wrap gap-4 pt-6">
      <div class="align-center d-flex gap-3">
        Check Running Const of Machines
      </div>
      <VSpacer />
      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <VBtn
          variant="tonal"
          prepend-icon="tabler-upload"
          @click="handleExport"
          :loading="loadingExport"
        >
          Export
        </VBtn>
        <AppAutocomplete
          v-model="year"
          :items="years"
          outlined
          @update:model-value="fetchData()"
        />
      </div>
    </VCardTitle>

    <VRow class="mx-2 my-4">
      <VCol>
        <AppAutocomplete
          v-model="month"
          :items="months"
          placeholder="Select month"
          item-title="label"
          clear-icon="tabler-x"
          return-object
          outlined
          clearable
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="shop"
          placeholder="Select shop"
          item-title="title"
          :items="shops"
          clear-icon="tabler-x"
          outlined
          return-object
          clearable
        />
      </VCol>
      <VCol>
        <VSelect
          v-model="machine"
          placeholder="Select machine"
          item-title="title"
          :items="[]"
          clear-icon="tabler-x"
          outlined
          return-object
          clearable
          readonly
          @click="isSelectMachineDialogVisible = !isSelectMachineDialogVisible"
        />
      </VCol>
      <VCol>
        <AppTextField v-model="plantCode" placeholder="Plant code" />
      </VCol>
    </VRow>

    <div
      v-if="isLoading"
      class="d-flex flex-column align-center justify-center my-8"
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

    <VCard v-else variant="outlined" class="ma-4">
      <div class="v-table-row-odd-even">
        <VTable
          height="600"
          fixed-header
          class="text-no-wrap machine-cost-table"
        >
          <thead>
            <tr>
              <th class="no-column">NO.</th>
              <th class="machine-name-column">MACHINE NAME</th>
              <th class="machine-no-column">MACHINE NO</th>
              <th class="shop-column">SHOP</th>
              <th class="line-column">LINE</th>
              <th class="cost-column">COST</th>
              <th>UNIT</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in data" :key="index">
              <td class="no-column">{{ row.index }}</td>
              <td class="machine-name-column">{{ row.machinename }}</td>
              <td class="machine-no-column">{{ row.machineno }}</td>
              <td class="shop-column">{{ row.shopname }}</td>
              <td class="line-column">{{ row.linecode }}</td>
              <td class="cost-column">{{ row.price }}</td>
              <td>Rp.</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>
  </VCard>

  <SelectMachineDialog
    v-model:isDialogVisible="isSelectMachineDialogVisible"
    @submit="handleMachinesSelected"
  />
</template>

<style scoped>
.machine-cost-table {
  width: 100%;
}

.machine-cost-table th,
.machine-cost-table td {
  padding: 12px;
  max-width: 300px;
  white-space: normal;
  word-wrap: break-word;
}

/* Specific column styles */
.no-column {
  width: 60px;
  min-width: 60px;
}

.machine-name-column {
  min-width: 100px;
}

.machine-no-column {
  min-width: 120px;
}

.shop-column {
  min-width: 100px;
}

.line-column {
  min-width: 80px;
}

.cost-column {
  min-width: 120px;
  /* text-align: right; */
}
</style>
