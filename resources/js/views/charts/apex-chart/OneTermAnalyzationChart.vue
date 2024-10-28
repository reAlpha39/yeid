<script setup>
import { getDonutChartConfig } from "@core/libs/apex-chart/apexCharConfig";
  import { useTheme } from "vuetify";
import { VDivider } from "vuetify/lib/components/index.mjs";

const vuetifyTheme = useTheme();

const data = ref();
const series = ref([]);
const labels = ref([]);
const colors = ref([]);

const columnLabels = {
  code: "Code",
  shop: "Shop",
  line_name: "Line Name",
  nama: "Name",
  machine_header: "Machine Header",
  machine_no: "Machine No",
  uraian_masalah: "Uraian Masalah",
  penyebab: "Penyebab",
  tindakan: "Tindakan",
  solution: "Solution",
  stop_panjang: "Stop Panjang",
  maker_name: "Maker Name",
  item_count: "Item Count",
};

function getRandomColor() {
  return `#${Math.floor(Math.random() * 16777215).toString(16)}`;
}

async function fetchData() {
  try {
    const response = await $api("/maintenance-database-system/analyze", {
      method: "GET",
      body: {
        targetTerm: null,
        targetItem: 1,
        targetSum: 0,
        startYear: 2023,
        startMonth: 11,
        endYear: 2023,
        endMonth: 11,
        targetSort: 0,
        outofRank: true,
      },
    });

    data.value = response.data.map((item, index) => ({
      ...item,
      color: getRandomColor(),
    }));

    series.value = data.value.map((item) => item.item_count);
    labels.value = data.value.map((item) => item.code);
    colors.value = data.value.map((item) => item.color);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

const expenseRationChartConfig = computed(() =>
  getDonutChartConfig(vuetifyTheme.current.value, labels.value, colors.value)
);

onMounted(() => {
  fetchData();
});
</script>

<template>
  <span
    class="d-block font-weight-medium text-high-emphasis text-truncate"
    style="font-size: 18px"
  >
    Grafik
  </span>
  <br />
  <VueApexCharts
    type="donut"
    height="410"
    :options="expenseRationChartConfig"
    :series="series"
  />

  <VDivider />

  <span
    class="d-block font-weight-medium text-high-emphasis text-truncate"
    style="font-size: 18px"
  >
    Detail
  </span>

  <!-- table here -->
</template>
