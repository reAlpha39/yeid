// PeriodicActivityChart.vue
<script setup>
import { getColumnChartConfig } from "@core/libs/apex-chart/apexCharConfig";
import { computed, onMounted, ref, watch } from "vue";
import { useToast } from "vue-toastification";
import VueApexCharts from "vue3-apexcharts";
import { useTheme } from "vuetify";

const vuetifyTheme = useTheme();
const toast = useToast();

const isLoading = ref(false);

const shops = ref([]);
const years = ref([]);
const shopCode = ref(null);
const now = new Date();
const currentYear = now.getFullYear();
const year = ref(currentYear);
const data = ref([]);

// Computed property for chart series and configuration
const chartData = computed(() => {
  if (!data.value.length) return { series: [], labels: [] };

  // Filter data based on selected shop if any
  const filteredData = shopCode.value
    ? data.value.filter((item) => item.shop_id === shopCode.value.shopcode)
    : data.value;

  // Initialize monthly counters
  const months = Array(12)
    .fill()
    .map(() => ({
      completed: 0,
      overdue: 0,
      pending: 0,
    }));

  // Process each activity's tasks and executions
  filteredData.forEach((activity) => {
    activity.tasks.forEach((task) => {
      task.executions.forEach((execution) => {
        // Get month from scheduled_week (approximately week/4)
        const month = Math.floor((execution.scheduled_week - 1) / 4);
        if (month >= 0 && month < 12) {
          if (execution.status === "completed") {
            months[month].completed++;
          } else if (execution.status === "pending") {
            months[month].pending++;
          } else {
            months[month].overdue++;
          }
        }
      });
    });
  });

  // Prepare series data
  const series = [
    {
      name: "Sudah dilakukan (On Time)",
      data: months.map((m) => m.completed),
    },
    {
      name: "Sudah dilakukan (Delay)",
      data: months.map((m) => m.overdue),
    },
    {
      name: "Belum dilakukan",
      data: months.map((m) => m.pending),
    },
  ];

  // Month labels
  const labels = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agu",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ];

  return { series, labels };
});

// Chart configuration
const chartConfig = computed(() => {
  const colors = ["#4CAF50", "#FFA726", "#EF5350"]; // Green, Orange, Red
  const baseConfig = getColumnChartConfig(
    vuetifyTheme.current.value,
    chartData.value.labels,
    colors
  );

  // console.log(chartData.value.series);

  return {
    ...baseConfig,
    chart: {
      ...baseConfig.chart,
      stacked: true,
      parentHeightOffset: 0,
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: true,
          zoom: true,
          zoomin: true,
          zoomout: true,
          pan: true,
          reset: true,
          customIcons: [],
        },
      },
    },
    plotOptions: {
      bar: {
        columnWidth: "30%",
        borderRadius: 0,
      },
    },
    legend: {
      ...baseConfig.legend,
      position: "bottom",
      horizontalAlign: "center",
    },
    yaxis: {
      min: 0,
      labels: {
        style: { fontSize: "0.875rem" },
        formatter: (value) => Math.round(value),
      },
      decimalsInFloat: 0,
      step: 1, // This ensures intervals of 1
      forceNiceScale: true, // This helps maintain clean intervals
    },
  };
});

// Series data for the chart
const series = computed(() => chartData.value.series);

// Add computed property for table data
const tableData = computed(() => {
  if (!data.value.length) return [];

  const months = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
  ];

  const monthlyStats = months.map((month, index) => {
    const stats = {
      month,
      completed: 0,
      overdue: 0,
      pending: 0,
      total: 0,
      percentage: 0,
    };

    // Filter and process data for each month
    const filteredData = shopCode.value
      ? data.value.filter((item) => item.shop_id === shopCode.value.shopcode)
      : data.value;

    filteredData.forEach((activity) => {
      activity.tasks.forEach((task) => {
        task.executions.forEach((execution) => {
          const executionMonth = Math.floor((execution.scheduled_week - 1) / 4);
          if (executionMonth === index) {
            if (execution.status === "completed") {
              stats.completed++;
            } else if (execution.status === "pending") {
              stats.pending++;
            } else {
              stats.overdue++;
            }
          }
        });
      });
    });

    // Calculate total and percentage
    stats.total = stats.completed + stats.overdue + stats.pending;
    stats.percentage = stats.total
      ? (((stats.completed + stats.overdue) / stats.total) * 100).toFixed(0) +
        "%"
      : "0%";

    return stats;
  });

  return monthlyStats;
});

function getLastTenYears() {
  for (let i = 0; i <= 10; i++) {
    years.value.push(currentYear - i);
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    shops.value = response.data;
    shops.value.forEach((data) => {
      data.title = data.shopcode + " | " + data.shopname;
    });
  } catch (err) {
    console.error("Failed to fetch shops:", err);
  }
}

async function fetchData() {
  try {
    isLoading.value = true;

    const response = await $api("/schedule/activities/table", {
      params: {
        year: year.value,
        shop_id: shopCode.value?.shopcode,
      },
    });

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch activity data");
    console.error(err);
  } finally {
    isLoading.value = false;
  }
}

// Watch for changes in shop or year selection
watch([shopCode, year], () => {
  fetchData();
});

onMounted(() => {
  fetchData();
  fetchDataShop();
  getLastTenYears();
});
</script>

<template>
  <VCard class="mb-6">
    <VCardText class="py-4 px-6">
      <span class="text-h5 font-weight-medium">Grafik Periodical Activity</span>
    </VCardText>

    <VRow class="px-6">
      <VCol cols="3">
        <AppAutocomplete
          v-model="shopCode"
          placeholder="Select shop"
          item-title="title"
          :items="shops"
          clear-icon="tabler-x"
          outlined
          return-object
          clearable
        />
      </VCol>
      <VCol cols="2">
        <AppAutocomplete v-model="year" :items="years" outlined />
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

    <div
      v-else-if="!tableData.length"
      class="d-flex flex-column align-center justify-center mt-6 mb-2"
    >
      <!-- <VIcon icon="tabler-database-off" size="48" color="grey-lighten-1" /> -->
      <VCardText class="text-center text-body-1 text-medium-emphasis">
        No data found
      </VCardText>
    </div>

    <div v-else class="px-6">
      <VueApexCharts
        type="bar"
        height="410"
        :options="chartConfig"
        :series="series"
      />

      <div class="v-table-row-odd-even">
        <VTable fixed-header class="periodic-activity-table py-6">
          <thead>
            <tr>
              <th class="text-left">BULAN</th>
              <th class="text-left">
                SUDAH DILAKUKAN<br />
                (ON TIME)
              </th>
              <th class="text-left">SUDAH DILAKUKAN<br />(DELAY)</th>
              <th class="text-left">BELUM<br />DILAKUKAN</th>
              <th class="text-left">TOTAL</th>
              <th class="text-left">% PERIODICAL<br />ACTIVITY</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableData" :key="row.month">
              <td class="text-left">{{ row.month }}</td>
              <td class="text-left">{{ row.completed }}</td>
              <td class="text-left">{{ row.overdue }}</td>
              <td class="text-left">{{ row.pending }}</td>
              <td class="text-left">{{ row.total }}</td>
              <td class="text-left">{{ row.percentage }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </div>
  </VCard>
</template>

<style scoped>
.v-card {
  border-radius: 6px;
  box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.1);
}

.v-card-title {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.periodic-activity-table {
  width: 100%;
  border-collapse: collapse;
}

.periodic-activity-table th,
.periodic-activity-table td {
  padding: 12px;
  border: 1px solid rgba(0, 0, 0, 0.12);
}

.periodic-activity-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.periodic-activity-table tr:hover {
  background-color: #f5f5f5;
}
</style>
