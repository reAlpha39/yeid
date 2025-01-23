<script setup>
import axios from "axios";
import { computed, onMounted, ref } from "vue";
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
const isGraphDialogVisible = ref(false);

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

const rows = [
  { type: "Import", category: "M" },
  { type: "Local", category: "M" },
  { type: "Import", category: "J" },
  { type: "Local", category: "J" },
  { type: "Import", category: "F" },
  { type: "Local", category: "F" },
  { type: "Import", category: "O" },
  { type: "Local", category: "O" },
];

const groupedFactories = computed(() => {
  const factories = {};

  data.value.forEach((item) => {
    if (!factories[item.plant_code]) {
      factories[item.plant_code] = {
        plantCode: item.plant_code,
        data: [],
      };
    }
    factories[item.plant_code].data.push(item);
  });

  return Object.values(factories);
});

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}

async function fetchData() {
  try {
    isLoading.value = true;
    const response = await $api(
      "/maintenance-database-system/spare-part-referring/parts-cost",
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
      "/api/maintenance-database-system/spare-part-referring/parts-cost/export",
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
    link.download = "parts_cost.xlsx";
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    loadingExport.value = false;
  }
}

function getCellValue(factoryData, type, category, month) {
  const typePrefix = type === "Import" ? "I" : "L";
  const partCode = `${typePrefix}${category}`;

  const entry = factoryData.find(
    (item) => item.part_code === partCode && item.job_month === month
  );

  return entry ? entry.total_cost_idr : "-";
}

function calculateSubTotal(factoryData, month) {
  const monthData = factoryData.filter((item) => item.job_month === month);
  if (!monthData.length) return "-";

  const total = monthData.reduce((sum, item) => {
    return sum + parseFloat(item.total_cost_idr);
  }, 0);

  return total.toFixed(1);
}

// const chartData = computed(() => {
//   const series = rows.map((row) => ({
//     name: `${row.type} ${row.category}`,
//     data: months.map((month) => {
//       const total = groupedFactories.value.reduce((sum, factory) => {
//         const value = getCellValue(
//           factory.data,
//           row.type,
//           row.category,
//           month.value
//         );
//         return sum + (value === "-" ? 0 : parseFloat(value));
//       }, 0);
//       return total;
//     }),
//   }));

//   return {
//     series,
//     labels: months.map((month) => month.label.slice(0, 3)),
//   };
// });

// const chartConfig = computed(() => {
//   const colors = [
//     "#4CAF50",
//     "#FF9800",
//     "#2196F3",
//     "#E91E63",
//     "#9C27B0",
//     "#795548",
//     "#607D8B",
//     "#FF5722",
//   ];

//   return {
//     chart: {
//       type: "bar",
//       stacked: true,
//       toolbar: { show: true },
//       parentHeightOffset: 0,
//     },
//     plotOptions: {
//       bar: {
//         horizontal: false,
//         columnWidth: "30%",
//         borderRadius: 0,
//       },
//     },
//     colors,
//     xaxis: {
//       categories: chartData.value.labels.map((month, index) => {
//         const totalCost = chartData.value.series.reduce((sum, serie) => {
//           return sum + (serie.data[index] || 0);
//         }, 0);
//         return `${month} (${totalCost.toFixed(1)})`;
//       }),
//       labels: {
//         style: { fontSize: "12px" },
//       },
//     },
//     yaxis: {
//       title: {
//         text: "Cost (Jt. IDR)",
//       },
//       labels: {
//         formatter: (value) => Math.round(value),
//       },
//     },
//     legend: {
//       position: "bottom",
//       horizontalAlign: "center",
//     },
//     dataLabels: {
//       enabled: false,
//     },
//     tooltip: {
//       y: {
//         formatter: (value) => `${value.toFixed(1)} Jt. IDR`,
//       },
//     },
//   };
// });

onMounted(() => {
  getLastTenYears();
  fetchData();
});
</script>

<template>
  <VCard class="mt-5">
    <VCardTitle class="d-flex flex-wrap gap-4 pt-6">
      <div class="align-center d-flex gap-3">Result Const Of Used Parts</div>
      <VSpacer />
      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <VBtn
          v-if="!isLoading && groupedFactories.length > 0"
          variant="tonal"
          prepend-icon="tabler-graph"
          @click="isGraphDialogVisible = !isGraphDialogVisible"
        >
          Graph
        </VBtn>
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

    <!-- <VCard outlined class="ma-4">
      <VCardTitle>Grafik Spare Part Cost Yeary Chart</VCardTitle>
      <VueApexCharts
        height="410"
        :options="chartConfig"
        :series="chartData.series"
      />
    </VCard> -->

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

    <div v-else-if="groupedFactories.length === 0">
      <VCardText class="text-center text-body-1 text-medium-emphasis">
        No data available
      </VCardText>
    </div>

    <VCard
      v-else
      v-for="factory in groupedFactories"
      :key="factory.plantCode"
      class="ma-4"
      variant="outlined"
    >
      <VCardTitle class="my-4"> Factory {{ factory.plantCode }} </VCardTitle>

      <VDivider />
      <VTable>
        <thead>
          <tr>
            <th>IMPORT/<br />LOCAL</th>
            <th>CAT</th>
            <th>UNIT</th>
            <th v-for="month in months" :key="month.value">
              {{ month.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(row, index) in rows" :key="index">
            <tr>
              <td>{{ row.type }}</td>
              <td>{{ row.category }}</td>
              <td>Jt. IDR</td>
              <td
                v-for="month in months"
                :key="`${row.type}-${row.category}-${month.value}`"
              >
                {{
                  getCellValue(
                    factory.data,
                    row.type,
                    row.category,
                    month.value
                  )
                }}
              </td>
            </tr>
          </template>
          <tr class="font-weight-bold" style="background-color: #f9f9f9">
            <td colspan="2">Sub Total</td>
            <td>Jt. IDR</td>
            <td v-for="month in months" :key="`subtotal-${month.value}`">
              {{ calculateSubTotal(factory.data, month.value) }}
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </VCard>

  <SparePartCostYearlyChartDialog
    v-model:isDialogVisible="isGraphDialogVisible"
    v-model:data="data"
  />
</template>

<style scoped>
.v-table {
  width: 100%;
}
.v-table th {
  white-space: nowrap;
  font-weight: bold;
}
.v-card-title {
  font-size: 1.25rem;
  font-weight: bold;
}
</style>
