<script setup>
import {
  getColumnChartConfig,
  getDonutChartConfig,
} from "@core/libs/apex-chart/apexCharConfig";
import { saveAs } from "file-saver";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import { debounce } from "lodash";
import { useToast } from "vue-toastification";
import { useTheme } from "vuetify";
import { VDivider } from "vuetify/lib/components/index.mjs";
import * as XLSX from "xlsx";

definePage({
  meta: {
    action: "view",
    subject: "mtDbsDbAnl",
  },
});

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

const isLoadingChart = ref(false);

const startDate = ref(formattedDate);
const endDate = ref(formattedDate);
const method = ref("One Term");
const seeOnly = ref("50");
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

async function resetFilter() {
  startDate.value = formattedDate;
  endDate.value = formattedDate;
  method.value = "One Term";
  seeOnly.value = "50";
  targetItem.value = "Jenis Perbaikan";
  targetSum.value = "Count";
  sort.value = "DESC";
  includeOtherCheckBox.value = false;
  maintenanceCode.value = null;
  shop.value = null;
  line.value = null;
  machineNo.value = null;
  situation.value = null;
  factor.value = null;
  measure.value = null;
  prevention.value = null;
  ltfactor.value = null;
  maker.value = null;
  counter.value = null;
  moreThan.value = null;
  lessThan.value = null;
  await fetchData();
}

const data = ref();
const series = ref([]);
const labels = ref([]);
const colors = ref([]);
const expenseRationChartConfig = ref();
const chartMultiTermConfig = ref();
const targetItemColumnName = ref();
const targetSumColumnName = ref();
const itemCountFieldName = ref();

function getRandomColor() {
  const randomColor = Math.floor(Math.random() * 16777215).toString(16);
  return `#${randomColor.padStart(6, "0")}`;
}

function calculateEndDate() {
  let startYear = null;
  let startMonth = null;
  let endYear = null;
  let endMonth = null;
  let endDay = null;

  switch (method.value) {
    case "4 Quarters":
    case "8 Quarters":
    case "12 Quarters":
    case "2 Halfs":
    case "4 Halfs":
    case "3 Years": {
      const date = new Date(startDate.value);
      startDate.value = formatDate(new Date(date.getFullYear(), 0, 1));

      break;
    }
  }

  const date = new Date(startDate.value);
  startYear = date.getFullYear();
  startMonth = date.getMonth() + 1; // Months are 0-indexed

  switch (method.value) {
    case "One Term": {
      const date = new Date(endDate.value);
      endYear = date.getFullYear();
      endMonth = date.getMonth() + 1;
      endDay = date.getDate();

      break;
    }

    case "Days": {
      endYear = startYear;
      endMonth = startMonth;
      const date = new Date(startYear, startMonth, 0);
      endDay = date.getDate(); // Last day of the start month

      endDate.value = formatDate(date);
      break;
    }

    case "3 Months":
    case "6 Months":
    case "12 Months":
    case "24 Months": {
      const monthsToAdd = parseInt(method.value.split(" ")[0]);
      const date = new Date(startYear, startMonth - 1 + monthsToAdd, 0); // Last day of the calculated month
      endYear = date.getFullYear();
      endMonth = date.getMonth() + 1;
      endDay = date.getDate();

      endDate.value = formatDate(date);
      break;
    }

    case "4 Quarters":
    case "8 Quarters":
    case "12 Quarters": {
      const quartersToAdd = parseInt(method.value.split(" ")[0]);
      const monthsToAdd = quartersToAdd * 3;
      const date = new Date(startYear, startMonth - 1 + monthsToAdd, 0);
      endYear = date.getFullYear();
      endMonth = date.getMonth() + 1;
      endDay = date.getDate();

      endDate.value = formatDate(date);
      break;
    }

    case "2 Halfs":
    case "4 Halfs": {
      const halvesToAdd = parseInt(method.value.split(" ")[0]);
      const monthsToAdd = halvesToAdd * 6;
      const date = new Date(startYear, startMonth - 1 + monthsToAdd, 0);
      endYear = date.getFullYear();
      endMonth = date.getMonth() + 1;
      endDay = date.getDate();

      endDate.value = formatDate(date);
      break;
    }

    case "3 Years": {
      const yearsToAdd = parseInt(method.value.split(" ")[0]);
      const date = new Date(startYear + yearsToAdd, startMonth - 1, 0);
      endYear = date.getFullYear();
      endMonth = date.getMonth() + 1;
      endDay = date.getDate();

      endDate.value = formatDate(date);
      break;
    }

    default:
      console.warn("Method not recognized");
      break;
  }

  return { endYear, endMonth, endDay };
}

function formatDate(date) {
  return new Intl.DateTimeFormat("en", {
    month: "short",
    year: "numeric",
  }).format(date);
}

// Create a debounced version of fetchData
const debouncedFetchData = debounce(() => {
  fetchData();
}, 500); // 500ms delay

async function fetchData() {
  isLoadingChart.value = true;
  series.value = [];

  try {
    let startYear = null;
    let startMonth = null;

    if (startDate.value) {
      const date = new Date(startDate.value);
      startYear = date.getFullYear();
      startMonth = date.getMonth() + 1; // Months are 0-indexed
    }

    const { endYear, endMonth } = calculateEndDate();
    // console.log(startDate.value);
    // console.log(endDate.value);
    // console.log(`Start Year: ${startYear}, Start Month: ${startMonth}`);
    // console.log(`End Year: ${endYear}, End Month: ${endMonth}`);

    const response = await $api("/maintenance-database-system/analyze", {
      method: "POST",
      body: {
        targetTerm:
          method.value === "One Term" ? null : methods.indexOf(method.value),
        targetItem: targetItems.indexOf(targetItem.value),
        targetSum: targetSums.indexOf(targetSum.value),
        startYear: startYear,
        startMonth: startMonth,
        endYear: endYear,
        endMonth: endMonth,
        tdivision:
          maintenanceCode.value !== null
            ? maintenanceCode.value.split("|")[0]
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
          counter.value !== null ? counters.indexOf(counter.value) + 1 : null,
        numMin: counter.value !== null ? parseInt(lessThan.value) : null,
        numMax: counter.value !== null ? parseInt(moreThan.value) : null,
        targetSort: sorts.indexOf(sort.value),
        maxRow: parseInt(seeOnly.value ?? "50"),
        outofRank: includeOtherCheckBox.value,
      },
    });

    if (method.value === "One Term") {
      data.value = response.data.map((item, index) => ({
        ...item,
        color:
          index < parseInt(seeOnly.value ?? "50")
            ? getRandomColor()
            : "#5C4646",
      }));

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

      expenseRationChartConfig.value = getDonutChartConfig(
        vuetifyTheme.current.value,
        labels.value,
        colors.value
      );

      if (data.value.length > 0) {
        const firstItemKeys = Object.keys(data.value[0]);

        targetItemColumnName.value =
          firstItemKeys.find((key) => columnLabels[key]) || "";

        const itemCountField =
          firstItemKeys.find((key) => itemCountFields.includes(key)) || "";

        const itemCountFieldIndex = itemCountFields.indexOf(itemCountField);
        targetSumColumnName.value =
          itemCountFieldIndex >= 0 ? targetSums[itemCountFieldIndex] : "";

        itemCountFieldName.value = itemCountField;
      }
    } else {
      const rawData = response.data;
      const groupedData = {};
      const codes = new Set();
      const codeDetails = new Map(); // Store additional details for each code

      if (rawData.length === 0) {
        isLoadingChart.value = false;
        return;
      }

      // Find the item count field from the first data item
      const firstItem = rawData[0];
      const itemCountField =
        Object.keys(firstItem).find((key) => itemCountFields.includes(key)) ||
        "count";

      // Process raw data and store code details
      rawData.forEach((item) => {
        const code = item.code;
        const itemCount = parseInt(item[itemCountField]);

        if (!groupedData[item.term]) {
          groupedData[item.term] = {};
        }

        groupedData[item.term][code] = itemCount;
        codes.add(code);

        // Store all available columns from columnLabels
        if (!codeDetails.has(code)) {
          const details = {
            code: item.code,
          };

          // Add all available fields from columnLabels
          Object.keys(columnLabels).forEach((key) => {
            if (key !== itemCountField && item[key] !== undefined) {
              details[key] = item[key];
            }
          });

          codeDetails.set(code, details);
        }
      });

      // Set up term-based placeholders based on method
      let termCount;
      let termFormat;
      let methodType;
      let startDateObj = new Date(startDate.value);
      const startYear = startDateObj.getFullYear();
      const startMonth = startDateObj.getMonth() + 1;

      switch (method.value) {
        case "Days":
          termCount = new Date(startYear, startMonth, 0).getDate();
          methodType = "Days";
          termFormat = (day) => {
            const date = new Date(startDateObj);
            date.setDate(date.getDate() + day);
            return formatDateToTerm(date, methodType);
          };
          break;

        case "3 Months":
        case "6 Months":
        case "12 Months":
        case "24 Months":
          termCount = parseInt(method.value.split(" ")[0]);
          methodType = "Month";
          termFormat = (monthOffset) => {
            const termDate = new Date(startDateObj);
            termDate.setMonth(startDateObj.getMonth() + monthOffset);
            return formatDateToTerm(termDate, methodType);
          };
          break;

        case "4 Quarters":
        case "8 Quarters":
        case "12 Quarters":
          termCount = parseInt(method.value.split(" ")[0]);
          methodType = "Quarter";
          termFormat = (quarterOffset) => {
            const termDate = new Date(startDateObj);
            termDate.setMonth(startDateObj.getMonth() + quarterOffset * 3);
            return formatDateToTerm(termDate, methodType);
          };
          break;

        case "2 Halfs":
        case "4 Halfs":
          termCount = parseInt(method.value.split(" ")[0]);
          methodType = "Half";
          termFormat = (halfOffset) => {
            const termDate = new Date(startDateObj);
            termDate.setMonth(startDateObj.getMonth() + halfOffset * 6);
            return formatDateToTerm(termDate, methodType);
          };
          break;

        case "3 Years":
          termCount = 3;
          methodType = "Year";
          termFormat = (yearOffset) => {
            const termDate = new Date(startDateObj);
            termDate.setFullYear(startDateObj.getFullYear() + yearOffset);
            return formatDateToTerm(termDate, methodType);
          };
          break;

        default:
          termCount = 1;
          methodType = "Month";
      }

      // Generate terms array based on termFormat
      const terms = Array.from({ length: termCount }, (_, idx) => {
        if (method.value === "Days") {
          return termFormat(idx);
        } else {
          return termFormat(idx);
        }
      });

      // Calculate total counts for each code across all terms
      const totalCounts = {};
      codes.forEach((code) => {
        totalCounts[code] = terms.reduce(
          (sum, term) => sum + (groupedData[term]?.[code] || 0),
          0
        );
      });

      // Transform the data for the table similar to One Term format
      const unsortedData = Array.from(codes).map((code, index) => {
        const codeDetail = codeDetails.get(code);
        return {
          ...codeDetail,
          color: getRandomColor(),
          [itemCountField]: totalCounts[code],
        };
      });

      // Sort the data based on the item count field and sort direction
      data.value = unsortedData.sort((a, b) => {
        const aValue = a[itemCountField] || 0;
        const bValue = b[itemCountField] || 0;
        return sort.value === "DESC" ? bValue - aValue : aValue - bValue;
      });

      // Limit the data based on seeOnly value
      data.value = data.value.slice(0, parseInt(seeOnly.value ?? "50"));

      // Map colors and series data in the same order as the sorted data
      const sortedCodes = data.value.map((item) => item.code);

      // Build series with zeros where data is missing
      series.value = sortedCodes.map((code) => ({
        name: code,
        data: terms.map((term) => groupedData[term]?.[code] || 0),
        color:
          data.value.find((item) => item.code === code)?.color ||
          getRandomColor(),
      }));

      labels.value = terms.map((term) => formatDisplayLabel(term, methodType));
      colors.value = series.value.map((item) => item.color);

      chartMultiTermConfig.value = getColumnChartConfig(
        vuetifyTheme.current.value,
        labels.value,
        colors.value
      );

      // Set column names for the table
      if (data.value.length > 0) {
        const firstItemKeys = Object.keys(data.value[0]);

        // Find the first available column from columnLabels
        targetItemColumnName.value =
          firstItemKeys.find(
            (key) => columnLabels[key] && key !== itemCountField
          ) || "";

        // Set itemCountFieldName using the dynamic field
        itemCountFieldName.value = itemCountField;

        // Set targetSumColumnName based on the itemCountField
        const itemCountFieldIndex = itemCountFields.indexOf(itemCountField);
        targetSumColumnName.value =
          itemCountFieldIndex >= 0
            ? targetSums[itemCountFieldIndex]
            : columnLabels[itemCountField] || "";
      }
    }

    isLoadingChart.value = false;
  } catch (err) {
    toast.error("Failed to fetch data");
    isLoadingChart.value = false;
    console.log(err);
  }
}

// Helper function to format date to match API format
const formatDateToTerm = (date, methodType) => {
  const year = date.getFullYear();

  switch (methodType) {
    case "Days":
      const month = (date.getMonth() + 1).toString().padStart(2, "0");
      const day = date.getDate().toString().padStart(2, "0");
      return `${year}${month}${day}`;

    case "Half":
      const half = date.getMonth() < 6 ? "1" : "2";
      return `${year}${half}`;

    case "Quarter":
      const quarter = Math.floor(date.getMonth() / 3) + 1;
      return `${year}${quarter}`;

    case "Year":
      return year.toString();

    default:
      const paddedMonth = (date.getMonth() + 1).toString().padStart(2, "0");
      return `${year}${paddedMonth}`;
  }
};

// Helper function to format display labels
const formatDisplayLabel = (term, methodType) => {
  switch (methodType) {
    case "Days":
      // Input: "20241016" -> Output: "10/16"
      return `${term.slice(4, 6)}/${term.slice(6)}`;

    case "Month":
      // Input: "202410" -> Output: "2024/10"
      return `${term.slice(0, 4)}/${term.slice(4)}`;

    case "Quarter":
      // Input: "20241" -> Output: "2024Q1"
      return `${term.slice(0, 4)}Q${term.slice(4)}`;

    case "Half":
      // Input: "20241" -> Output: "2024H1"
      return `${term.slice(0, 4)}H${term.slice(4)}`;

    case "Year":
      // Input: "2024" -> Output: "2024"
      return term;

    default:
      return term;
  }
};

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

const exportGraph = () => {
  const chart = document.querySelector(".apexcharts-canvas");
  if (chart) {
    // Get the SVG data
    const svgData = chart.querySelector("svg").outerHTML;
    const svgBlob = new Blob([svgData], {
      type: "image/svg+xml;charset=utf-8",
    });

    saveAs(
      svgBlob,
      `maintenance-chart-${new Date().toISOString().split("T")[0]}.svg`
    );
  }
};

const exportTableToExcel = () => {
  if (!data.value?.length) return;

  // Prepare the data for export
  const exportData = data.value.map((item) => ({
    Code: item.code,
    [targetItemColumnName.value]: item[targetItemColumnName.value],
    [targetSumColumnName.value]: item[itemCountFieldName.value],
  }));

  // Create worksheet
  const ws = XLSX.utils.json_to_sheet(exportData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Maintenance Data");

  // Generate Excel file
  XLSX.writeFile(
    wb,
    `maintenance-data-${new Date().toISOString().split("T")[0]}.xlsx`
  );
};

const exportTableToCSV = () => {
  if (!data.value?.length) return;

  // Prepare CSV content
  const headers = [
    "Code",
    targetItemColumnName.value,
    targetSumColumnName.value,
  ].join(",");
  const rows = data.value.map((item) => {
    return [
      item.code,
      item[targetItemColumnName.value],
      item[itemCountFieldName.value],
    ].join(",");
  });

  const csvContent = [headers, ...rows].join("\n");
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  saveAs(
    blob,
    `maintenance-data-${new Date().toISOString().split("T")[0]}.csv`
  );
};
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

const key = ref(0);
watch(startDate, (newStartDate) => {
  if (endDate.value && new Date(endDate.value) < new Date(newStartDate)) {
    endDate.value = newStartDate;
  }

  key.value++;
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
            mode: 'single',
            enableTime: false,
            enableSeconds: false,
            plugins: [
              new monthSelectPlugin({
                shorthand: true,
                dateFormat: 'M Y',
                altFormat: 'M Y',
              }),
            ],
          }"
          append-inner-icon="tabler-calendar"
          @update:modelValue="fetchData()"
        />
      </VCol>
      <VCol>
        <AppDateTimePicker
          :key="key"
          class="pt-1"
          v-model="endDate"
          label=" "
          :placeholder="endDate"
          :config="{
            dateFormat: 'M Y',
            mode: 'single',
            enableTime: false,
            enableSeconds: false,
            minDate: startDate,
            plugins: [
              new monthSelectPlugin({
                shorthand: true,
                dateFormat: 'M Y',
                altFormat: 'M Y',
              }),
            ],
          }"
          append-inner-icon="tabler-calendar"
          :disabled="method !== 'One Term'"
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
          v-on:input="debouncedFetchData()"
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
          v-on:input="debouncedFetchData()"
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
          v-on:input="debouncedFetchData()"
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
          v-on:input="debouncedFetchData()"
        />
      </VCol>
    </VRow>

    <br />
    <VBtn @click="resetFilter"> Reset Filter </VBtn>
  </VCard>

  <br />

  <VCard title="Chart" v-if="!isLoadingChart">
    <div v-if="series.length !== 0">
      <VRow class="px-7 mt-2">
        <VCol>
          <VBtnGroup>
            <VBtn
              prepend-icon="tabler-chart-bar"
              @click="exportGraph"
              :disabled="!series.length"
              color="primary"
            >
              Export Graph
            </VBtn>
            <VBtn
              prepend-icon="tabler-file-spreadsheet"
              @click="exportTableToExcel"
              :disabled="!data?.length"
              color="success"
            >
              Export to Excel
            </VBtn>
            <!-- <VBtn
              prepend-icon="tabler-file-csv"
              @click="exportTableToCSV"
              :disabled="!data?.length"
              color="info"
            >
              Export to CSV
            </VBtn> -->
          </VBtnGroup>
        </VCol>
      </VRow>
      <VCardText>
        <VueApexCharts
          :type="method === 'One Term' ? 'donut' : 'bar'"
          height="410"
          :options="
            method === 'One Term'
              ? expenseRationChartConfig
              : chartMultiTermConfig
          "
          :series="series"
        />
      </VCardText>

      <VDivider />

      <VCardTitle class="pl-7 mt-4"> Detail</VCardTitle>

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
            </td>
            <td>{{ item.code }}</td>
            <td>{{ item[targetItemColumnName] }}</td>
            <td>
              {{ Intl.NumberFormat().format(item[itemCountFieldName] ?? 0) }}
            </td>
          </tr>
        </tbody>
      </VTable>

      <br />
    </div>
    <div v-else>
      <VCardText>
        <VCardText class="my-4 justify-center" style="text-align: center">
          Data tidak ditemukan
        </VCardText>
      </VCardText>
    </div>
  </VCard>

  <VCard title="Chart" v-else>
    <VCardText class="text-center justify-center my-12">
      Loading Chart...
    </VCardText>
  </VCard>
</template>

<style scoped>
.color-column {
  width: 100px; /* Set the desired width */
}
</style>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>
