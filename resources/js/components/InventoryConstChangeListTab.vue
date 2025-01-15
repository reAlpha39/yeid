<script setup>
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();

const now = new Date();
const currentYear = now.getFullYear();

const years = ref([]);
const year = ref(currentYear);

const data = ref([]);
const isLoading = ref(false);
const loadingExport = ref(false);

const months = [
  { value: 1, label: "JANUARI" },
  { value: 2, label: "FEBRUARI" },
  { value: 3, label: "MARET" },
  { value: 4, label: "APRIL" },
  { value: 5, label: "MEI" },
  { value: 6, label: "JUNI" },
  { value: 7, label: "JULI" },
  { value: 8, label: "AGUSTUS" },
  { value: 9, label: "SEPTEMBER" },
  { value: 10, label: "OKTOBER" },
  { value: 11, label: "NOVEMBER" },
  { value: 12, label: "DESEMBER" },
];

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}
async function fetchData() {
  try {
    isLoading.value = true;

    const response = await $api(
      "/maintenance-database-system/spare-part-referring/inventory-change-cost",
      {
        params: {
          year: year.value.toString(),
        },
      }
    );

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  } finally {
    isLoading.value = false;
  }
}

async function handleExport() {
  loadingExport.value = true;
  try {
    const accessToken = useCookie("accessToken").value;
    const response = await axios.get(
      "/api/maintenance-database-system/spare-part-referring/inventory-change-cost/export",
      {
        responseType: "blob",
        headers: accessToken
          ? {
              Authorization: `Bearer ${accessToken}`,
            }
          : {},
        params: {
          year: year.value.toString(),
        },
      }
    );

    const downloadUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = "inventory_change_cost.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

onMounted(() => {
  getLastTenYears();
  fetchData();
});
</script>

<template>
  <VCard class="mt-5">
    <VCardTitle class="d-flex flex-wrap gap-4 pt-6">
      <div class="align-center d-flex gap-3">
        Inventory Cost Change List(Top 100)
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
      <VTable height="600" fixed-header>
        <thead>
          <tr>
            <th style="max-width: 300px; min-width: 100px">PART</th>
            <th style="max-width: 300px; min-width: 100px">SPECIFICATION</th>
            <th
              v-for="month in months"
              :key="month.value"
              style="max-width: 300px; min-width: 100px"
            >
              {{ month.label }}<br />(JT. RP)
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in data" :key="index">
            <td style="max-width: 400px; min-width: 100px">
              {{ row.partname }}
              <br />
              <small>{{ row.partcode }}</small>
            </td>
            <td style="max-width: 400px; min-width: 100px">
              {{ row.specification }}
            </td>
            <td
              v-for="month in months"
              :key="month.value"
              style="max-width: 300px; min-width: 100px"
            >
              {{ row.monthly_data[month.value] }}
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </VCard>
</template>

<style scoped>
.table-cell {
  max-width: 300px;
  min-width: 70px;
}
</style>
