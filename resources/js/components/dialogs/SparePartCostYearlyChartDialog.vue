<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  data: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["update:isDialogVisible"]);

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

const data = ref([]);

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

function getCellValue(factoryData, type, category, month) {
  const typePrefix = type === "Import" ? "I" : "L";
  const partCode = `${typePrefix}${category}`;

  const entry = factoryData.find(
    (item) => item.part_code === partCode && item.job_month === month
  );

  return entry ? entry.total_cost_idr : "-";
}

const chartData = computed(() => {
  const series = rows.map((row) => ({
    name: `${row.type} ${row.category}`,
    data: months.map((month) => {
      const total = groupedFactories.value.reduce((sum, factory) => {
        const value = getCellValue(
          factory.data,
          row.type,
          row.category,
          month.value
        );
        return sum + (value === "-" ? 0 : parseFloat(value));
      }, 0);
      return total;
    }),
  }));

  return {
    series,
    labels: months.map((month) => month.label.slice(0, 3)),
  };
});

const chartConfig = computed(() => {
  const colors = [
    "#4CAF50",
    "#FF9800",
    "#2196F3",
    "#E91E63",
    "#9C27B0",
    "#795548",
    "#607D8B",
    "#FF5722",
  ];

  return {
    chart: {
      type: "bar",
      stacked: true,
      toolbar: { show: true },
      parentHeightOffset: 0,
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "30%",
        borderRadius: 0,
        dataLabels: {
          total: {
            enabled: true,
            formatter: function (_, opts) {
              const monthTotal = opts.w.globals.series.reduce(
                (sum, series) => sum + (series[opts.dataPointIndex] || 0),
                0
              );
              return monthTotal.toFixed(1);
            },
            style: {
              fontSize: "12px",
              fontWeight: 500,
            },
          },
        },
      },
    },
    dataLabels: {
      enabled: false,
    },
    colors,
    xaxis: {
      categories: chartData.value.labels,
      labels: {
        style: { fontSize: "12px" },
      },
    },
    yaxis: {
      title: {
        text: "Cost (Jt. IDR)",
      },
      labels: {
        formatter: (value) => parseFloat(value.toFixed(1)),
      },
    },
    legend: {
      position: "bottom",
      horizontalAlign: "center",
    },

    tooltip: {
      y: {
        formatter: (value) => `${value.toFixed(1)} Jt. IDR`,
      },
    },
  };
});

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      data.value = props.data;
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
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard outlined class="pa-6">
      <VCardTitle>Grafik Spare Part Cost Yeary Chart</VCardTitle>
      <VueApexCharts
        height="600"
        :options="chartConfig"
        :series="chartData.series"
      />
    </VCard>
  </VDialog>
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
