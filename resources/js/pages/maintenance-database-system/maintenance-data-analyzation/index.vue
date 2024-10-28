<script setup>
import { getDonutChartConfig } from "@core/libs/apex-chart/apexCharConfig";
import { useToast } from "vue-toastification";
import { useTheme } from "vuetify";
import { VDivider } from "vuetify/lib/components/index.mjs";

const toast = useToast();
const vuetifyTheme = useTheme();

const columnLabels = {
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

const itemCountFields = [
  "item_count",
  "machinestop",
  "linestop",
  "repair_manhour_internal",
  "maker_manhour",
  "total_maintenance_man_hour",
  "maker_cost",
  "part_cost",
  "staff_number",
  "wkt_sebelum_pekerjaan",
  "wkt_priodical_maintenance",
  "wkt_pertanyaan",
  "wkt_siapkan",
  "wkt_penelitian",
  "wkt_menunggu_part",
  "wkt_pekerjaan_maintenance",
  "wkt_konfirm",
];

const methods = [
  "One Term",
  "Days",
  "3 Months",
  "6 Months",
  "12 Months",
  "24 Months",
  "4 Quarters",
  "8 Quarters",
  "12 Quarters",
  "2 Halfs",
  "4 Halfs",
  "3 Years",
];

const targetItems = [
  "Jenis Perbaikan", // MAINTENANCECODE
  "Shop", // ORDERSHOP
  "Line", // MAS_MACHINE.LINE
  "Machine Header", // substr(MACHINENO,1,3)
  "Machine No", // MACHINENO
  "Uraian Masalah", // SITUATIONCODE
  "Penyebab", // FACTORCODE
  "Tindakan", // MEASURECODE
  "Solution", // PREVENTIONCODE
  "Stop Panjang", // LTFACTORCODE
  "Machine Maker", // MAS_MACHINE.MAKERCODE
];

const targetSums = [
  "Count", // count(RECORDID)
  "Waktu Machine Stop", // count(RECORDID)
  "Waktu Line Stop", // sum(LINESTOPTIME)
  "Repair ManHour (Internal)", // sum(TOTALREPAIRSUM * STAFFNUM)
  "Repair ManHour (Maker)", // sum(MAKERHOUR)
  "Repair ManHour Total", // sum((TOTALREPAIRSUM * STAFFNUM) + MAKERHOUR)
  "Maker Cost", // sum(MAKERSERVICE + MAKERPARTS)
  "Parts Cost", // sum(PARTCOSTCUM)
  "Staff Number", // sum(STAFFNUM)
  "Waktu Sebelum Pekerjaan", // sum(INACTIVESUM)
  "Waktu Periodical Maintenance", // sum(PERIODICALSUM)
  "Waktu Pertanyaan", // sum(QUESTIONSUM)
  "Waktu Siapkan", // sum(PREPARESUM)
  "Waktu Penelitian", // sum(CHECKSUM)
  "Waktu Menunggu Part", // sum(WAITSUM)
  "Waktu Pekerjaan Maintenance", // sum(REPAIRSUM)
  "Waktu Confirm", // sum(CONFIRMSUM)
];

const counters = [
  "Waktu Machine Stop", // sum(MACHINESTOPTIME)
  "Waktu Line Stop", // sum(LINESTOPTIME)
  "Repair ManHour (Internal)", // sum(TOTALREPAIRSUM * STAFFNUM)
  "Repair ManHour (Maker)", // sum(MAKERHOUR)
  "Repair ManHour Total", // sum((TOTALREPAIRSUM * STAFFNUM) + MAKERHOUR)
  "Maker Cost", // sum(MAKERSERVICE + MAKERPARTS)
  "Parts Cost", // sum(PARTCOSTCUM)
];

const maintenanceCodes = [
  "01|UM",
  "02|BM",
  "03|TBC",
  "04|TBA",
  "05|PvM",
  "06|FM",
  "07|CM",
  "08|CHECH",
  "09|LAYOUT",
];

const sorts = ["ASC", "DESC"];
const shops = ref([]);
const lines = ref([]);
const situations = ref([]);
const factors = ref([]);
const measures = ref([]);
const preventions = ref([]);
const ltfactors = ref([]);
const makers = ref([]);

const now = new Date();
const formattedDate = new Intl.DateTimeFormat("en", {
  month: "short",
  year: "numeric",
}).format(now);

const startDate = ref(formattedDate);
const endDate = ref(null);
const method = ref("One Term");
const seeOnly = ref();
const targetItem = ref("Jenis Perbaikan");
const targetSum = ref("Count");
const sort = ref("DESC");
const includeOtherCheckBox = ref(false);
const maintenanceCode = ref(null);
const shop = ref(null);
const line = ref(null);
const machineNo = ref(null);
const situation = ref(null);
const factor = ref(null);
const measure = ref(null);
const prevention = ref(null);
const ltfactor = ref(null);
const maker = ref(null);
const counter = ref(null);
const moreThan = ref(null);
const lessThan = ref(null);

function resetFilter() {}

const data = ref();
const series = ref([]);
const labels = ref([]);
const colors = ref([]);
const expenseRationChartConfig = ref();
const targetItemColumnName = ref();
const targetSumColumnName = ref();
const itemCountFieldName = ref();

function getRandomColor() {
  const randomColor = Math.floor(Math.random() * 16777215).toString(16);
  return `#${randomColor.padStart(6, "0")}`;
}

async function fetchData() {
  try {
    let startYear = null;
    let startMonth = null;
    let endYear = null;
    let endMonth = null;

    if (startDate.value) {
      const date = new Date(startDate.value);
      startYear = date.getFullYear();
      startMonth = date.getMonth() + 1; // Months are 0-indexed
    }

    if (endDate.value) {
      const date = new Date(endDate.value);
      endYear = date.getFullYear();
      endMonth = date.getMonth() + 1; // Months are 0-indexed
    }

    const response = await $api("/maintenance-database-system/analyze", {
      method: "POST",
      body: {
        targetTerm:
          method.value === "One Term" ? null : methods.indexOf(method),
        targetItem: targetItems.indexOf(targetItem.value),
        targetSum: targetSums.indexOf(targetSum.value),
        startYear: startYear,
        startMonth: startMonth,
        endYear: method.value === "One Term" ? startYear : endYear,
        endMonth: method.value === "One Term" ? startMonth : endMonth,
        tdivision:
          maintenanceCode.value !== null
            ? maintenanceCodes.indexOf(maintenanceCode.value)
            : null,
        section: shop.value?.shopcode,
        line: line.value?.linecode,
        machineNo: machineNo.value,
        situation: situation.value?.situationcode,
        measures: measure.value?.measurecode,
        factor: factor.value?.factorcode,
        factorLt: ltfactor.value?.ltfactorcode,
        preventive: prevention.value?.preventioncode,
        machineMaker: maker.value?.makercode,
        numItem:
          counter.value !== null ? counters.indexOf(counter.value) : null,
        numMin: counter.value !== null ? lessThan.value : null,
        numMax: counter.value !== null ? moreThan.value : null,
        targetSort: sorts.indexOf(sort.value),
        outofRank: includeOtherCheckBox.value,
      },
    });

    data.value = response.data.map((item, index) => ({
      ...item,
      color: getRandomColor(),
    }));

    console.log(data.value);

    series.value = data.value
      .map(
        (item) =>
          itemCountFields
            .map((field) => item[field])
            .find((value) => value !== undefined) || 0
      )
      .map((value) => parseInt(value, 10) || 0);

    labels.value = data.value.map((item) => item.code);
    colors.value = data.value.map((item) => item.color);

    console.log(series.value);
    console.log(labels.value);

    expenseRationChartConfig.value = getDonutChartConfig(
      vuetifyTheme.current.value,
      labels.value,
      colors.value
    );

    if (data.value.length > 0) {
      const firstItemKeys = Object.keys(data.value[0]);

      // Find the target item column name
      targetItemColumnName.value =
        firstItemKeys.find((key) => columnLabels[key]) || "";

      // Find the first matching itemCountField
      const itemCountField =
        firstItemKeys.find((key) => itemCountFields.includes(key)) || "";

      // Find the corresponding target sum based on the found itemCountField
      const itemCountFieldIndex = itemCountFields.indexOf(itemCountField);
      targetSumColumnName.value =
        itemCountFieldIndex >= 0 ? targetSums[itemCountFieldIndex] : "";

      // Store the field name for rendering in the table
      itemCountFieldName.value = itemCountField; // Store the dynamic field name
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchShop() {
  try {
    const response = await $api("/master/shops");

    shops.value = response.data;

    shops.value.forEach((data) => {
      data.title = data.shopcode + " | " + data.shopname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchLine() {
  try {
    const response = await $api("/master/lines");

    lines.value = response.data;
    lines.value.forEach((data) => {
      data.title = data.linecode + " | " + data.linename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchSituations() {
  try {
    const response = await $api("/master/situations");

    situations.value = response.data;

    situations.value.forEach((data) => {
      data.title = data.situationcode + " | " + data.situationname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchFactor() {
  try {
    const response = await $api("/master/factors");

    factors.value = response.data;

    factors.value.forEach((data) => {
      data.title = data.factorcode + " | " + data.factorname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchMeasure() {
  try {
    const response = await $api("/master/measures");

    measures.value = response.data;

    measures.value.forEach((data) => {
      data.title = data.measurecode + " | " + data.measurename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchPrevention() {
  try {
    const response = await $api("/master/preventions");

    preventions.value = response.data;

    preventions.value.forEach((data) => {
      data.title = data.preventioncode + " | " + data.preventionname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchLtfactors() {
  try {
    const response = await $api("/master/ltfactors");

    ltfactors.value = response.data;

    ltfactors.value.forEach((data) => {
      data.title = data.ltfactorcode + " | " + data.ltfactorname;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchMakers() {
  try {
    const response = await $api("/master/makers");

    makers.value = response.data;

    makers.value.forEach((data) => {
      data.title = data.makercode + " | " + data.makername;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

onMounted(() => {
  fetchData();
  fetchShop();
  fetchLine();
  fetchSituations();
  fetchFactor();
  fetchMeasure();
  fetchPrevention();
  fetchLtfactors();
  fetchMakers();
});

watch(
  () => method.value,
  // Whenever the method changes, ensure the checkbox value is reset if not "One Term"
  (newVal) => {
    if (newVal !== "One Term") {
      includeOtherCheckBox.value = false;
    }
  }
);
</script>

<template>
  <VBreadcrumbs
    class="px-0 pb-2 pt-0"
    :items="[
      {
        title: 'Maintenance Database System',
        class: 'text-h4',
      },
      {
        title: 'Maintenance Database Analyzation',
        class: 'text-h4',
      },
    ]"
  />

  <VCard class="pa-6">
    <VRow>
      <VCol>
        <AppDateTimePicker
          v-model="startDate"
          label="Term"
          placeholder="Oct 2024"
          :config="{
            dateFormat: 'M Y',
          }"
          type="month"
          append-inner-icon="tabler-calendar"
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppDateTimePicker
          class="pt-1"
          v-model="endDate"
          label=" "
          placeholder="Oct 2024"
          :config="{ dateFormat: 'M Y' }"
          append-inner-icon="tabler-calendar"
          :disabled="method === 'One Term'"
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="method"
          label="Method"
          placeholder="Select"
          :items="methods"
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppTextField
          v-model.number="seeOnly"
          label="Hanya Dilihat"
          suffix="Item"
          placeholder="0"
          outlined
          maxlength="3"
          @keypress="isNumber($event)"
          v-on:input="fetchData()"
        />
      </VCol>
    </VRow>

    <VRow>
      <VCol>
        <AppAutocomplete
          v-model="targetItem"
          label="Summary"
          placeholder="Select"
          :items="targetItems"
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="targetSum"
          label="Item Name"
          placeholder="Select"
          :items="targetSums"
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="sort"
          label="Sort"
          placeholder="Select"
          :items="sorts"
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <VCheckbox
          class="pt-7"
          label="Termasuk lain-lain"
          v-model="includeOtherCheckBox"
          :disabled="method !== 'One Term'"
          @update:modelValue="fetchData()"
        />
      </VCol>
    </VRow>

    <br />
    <VDivider />
    <br />

    <VRow>
      <VCol>
        <AppAutocomplete
          v-model="maintenanceCode"
          label="Perbaikan"
          placeholder="Select perbaikan"
          :items="maintenanceCodes"
          clearable
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="shop"
          label="Shop"
          placeholder="Select shop"
          :items="shops"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="line"
          label="Line"
          placeholder="Select line"
          :items="lines"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppTextField
          v-model="machineNo"
          label="Machine No"
          placeholder="Input machine no"
          maxlength="12"
          outlined
          v-on:input="fetchData()"
        />
      </VCol>
    </VRow>
    <VRow>
      <VCol>
        <AppAutocomplete
          v-model="situation"
          label="Uraian"
          placeholder="Select uraian"
          :items="situations"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="factor"
          label="Penyebab"
          placeholder="Select penyebab"
          :items="factors"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="measure"
          label="Tindakan"
          placeholder="Select tindakan"
          :items="measures"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppAutocomplete
          v-model="prevention"
          label="Solution"
          placeholder="Select solution"
          :items="preventions"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
    </VRow>
    <VRow>
      <VCol cols="3">
        <AppAutocomplete
          v-model="ltfactor"
          label="Stop Panjang"
          placeholder="Select stop Panjang"
          :items="ltfactors"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol cols="3">
        <AppAutocomplete
          v-model="maker"
          label="Mac Maker"
          placeholder="Select mac maker"
          :items="makers"
          clearable
          return-object
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol cols="3">
        <AppAutocomplete
          v-model="counter"
          label="Counter"
          placeholder="Select counter"
          :items="counters"
          clearable
          outlined
          @update:modelValue="fetchData()"
        />
      </VCol>
      <!-- Show only if a counter is selected -->
      <VCol v-if="counter">
        <AppTextField
          v-model.number="moreThan"
          :label="
            counter === 'Maker Cost' || counter === 'Parts Cost'
              ? 'Lebih (IDR)'
              : 'Lebih (Menit)'
          "
          placeholder="0"
          outlined
          maxlength="12"
          @keypress="isNumber($event)"
          v-on:input="fetchData()"
        />
      </VCol>

      <VCol v-if="counter">
        <AppTextField
          v-model.number="lessThan"
          :label="
            counter === 'Maker Cost' || counter === 'Parts Cost'
              ? 'Kurang (IDR)'
              : 'Kurang (Menit)'
          "
          placeholder="0"
          outlined
          maxlength="12"
          @keypress="isNumber($event)"
          v-on:input="fetchData()"
        />
      </VCol>
    </VRow>

    <br />
    <VBtn @click="resetFilter"> Reset Filter </VBtn>
  </VCard>

  <br />

  <VCard title="Grafik">
    <VCardText>
      <VueApexCharts
        v-if="series.length !== 0"
        type="donut"
        height="410"
        :options="expenseRationChartConfig"
        :series="series"
      />
      <VCardText v-else class="my-4 justify-center" style="text-align: center">
        Data tidak ditemukan
      </VCardText>
    </VCardText>

    <VDivider />

    <VCardTitle class="pl-7"> Detail</VCardTitle>

    <br />

    <VTable class="text-no-wrap px-7">
      <thead>
        <tr>
          <th></th>
          <th class="color-column">Code</th>
          <th>{{ targetItemColumnName }}</th>
          <th>{{ targetSumColumnName }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in data" :key="item.code">
          <td class="color-column" :style="{ backgroundColor: item.color }">
            <div style="width: 20px; height: 20px; border-radius: 4px"></div>
            <!-- Colored box -->
          </td>
          <td>{{ item.code }}</td>
          <td>{{ item[targetItemColumnName] }}</td>
          <td>{{ item[itemCountFieldName] }}</td>
        </tr>
      </tbody>
    </VTable>

    <br />
  </VCard>
</template>

<style scoped>
.color-column {
  width: 100px; /* Set the desired width */
}
</style>
