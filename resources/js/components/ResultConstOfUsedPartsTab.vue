<script setup>
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
