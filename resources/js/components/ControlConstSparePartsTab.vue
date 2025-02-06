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

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}
async function fetchData() {
  try {
    isLoading.value = true;

    const response = await $api(
      "/maintenance-database-system/spare-part-referring/inventory-summary",
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
      "/api/maintenance-database-system/spare-part-referring/inventory-summary/export",
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
    link.download = "inventory_summary.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

function formatNumber(value) {
  if (!value) return "-";
  return value.toString().replace(".", ",");
}

onMounted(() => {
  getLastTenYears();
  fetchData();
});
</script>

<template>
  <VCard class="mt-5">
    <VCardTitle class="d-flex flex-wrap gap-4 pt-6">
      <div class="align-center d-flex gap-3">Control Const Spare Parts</div>
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
      <div class="v-table-row-odd-even">
        <VTable>
          <thead>
            <tr>
              <th>ITEM</th>
              <th>UNIT</th>
              <th>JANUARI</th>
              <th>FEBRUARI</th>
              <th>MARET</th>
              <th>APRIL</th>
              <th>MEI</th>
              <th>JUNI</th>
              <th>JULI</th>
              <th>AGUSTUS</th>
              <th>SEPTEMBER</th>
              <th>OKTOBER</th>
              <th>NOVEMBER</th>
              <th>DESEMBER</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Order Amount</td>
              <td>Jt. IDR</td>
              <td>{{ formatNumber(data[0]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[1]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[2]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[3]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[4]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[5]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[6]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[7]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[8]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[9]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[10]?.order_in_millions) }}</td>
              <td>{{ formatNumber(data[11]?.order_in_millions) }}</td>
            </tr>
            <tr>
              <td>Received Amount</td>
              <td>Jt. IDR</td>
              <td>{{ formatNumber(data[0]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[1]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[2]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[3]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[4]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[5]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[6]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[7]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[8]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[9]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[10]?.inbound_in_millions) }}</td>
              <td>{{ formatNumber(data[11]?.inbound_in_millions) }}</td>
            </tr>
            <tr>
              <td>Spent Amount</td>
              <td>Jt. IDR</td>
              <td>{{ formatNumber(data[0]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[1]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[2]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[3]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[4]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[5]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[6]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[7]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[8]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[9]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[10]?.outbound_in_millions) }}</td>
              <td>{{ formatNumber(data[11]?.outbound_in_millions) }}</td>
            </tr>
            <tr>
              <td>Adjust Amount</td>
              <td>Jt. IDR</td>
              <td>{{ formatNumber(data[0]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[1]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[2]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[3]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[4]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[5]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[6]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[7]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[8]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[9]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[10]?.adjust_in_millions) }}</td>
              <td>{{ formatNumber(data[11]?.adjust_in_millions) }}</td>
            </tr>
            <tr>
              <td>Stock Amount</td>
              <td>Jt. IDR</td>
              <td>{{ formatNumber(data[0]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[1]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[2]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[3]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[4]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[5]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[6]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[7]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[8]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[9]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[10]?.stock_in_millions) }}</td>
              <td>{{ formatNumber(data[11]?.stock_in_millions) }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>
  </VCard>
</template>

<style scoped>
.v-table {
  width: 100%;
}

.v-table th {
  background-color: #f5f5f5 !important;
  color: rgba(0, 0, 0, 0.87) !important;
  font-weight: 500;
  text-align: left;
  padding: 12px 16px !important;
}

.v-table td {
  padding: 12px 16px !important;
  color: rgba(0, 0, 0, 0.87);
}

.v-table tbody tr:nth-of-type(even) {
  background-color: #fafafa;
}
</style>
