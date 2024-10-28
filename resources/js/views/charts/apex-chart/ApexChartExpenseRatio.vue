<script setup>
import { getDonutChartConfig } from "@core/libs/apex-chart/apexCharConfig";
import { useTheme } from "vuetify";

const vuetifyTheme = useTheme();
const expenseRationChartConfig = computed(() =>
  getDonutChartConfig(vuetifyTheme.current.value)
);

const series = [85, 16, 50, 50, 6, 66, 24, 54, 52];
</script>

<template>
  <VueApexCharts
    type="donut"
    height="410"
    :options="expenseRationChartConfig"
    :series="series"
  />
</template>
