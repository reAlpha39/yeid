<script setup>
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import moment from "moment";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "pressShot",
  },
});

const toast = useToast();
const router = useRouter();

const searchQuery = ref("");
const selectedMachineNo = ref(null);
const selectedModelDie = ref(null);
const isDetailDialogVisible = ref(false);

const selectedItem = ref("");

// Data table options
const itemsPerPage = ref(10);
const page = ref(1);

const date = ref(moment().format("YYYY-MM"));

const data = ref([]);
const modelDieData = ref([]);
const machineNoData = ref([]);

async function fetchData() {
  try {
    let targetDateSplit = date.value.split("-");
    const response = await $api("/press-shot/exchanges", {
      params: {
        search: searchQuery.value,
        target_date: targetDateSplit[0] + targetDateSplit[1],
        model: selectedModelDie.value?.model,
        die_no: selectedModelDie.value?.dieno,
        machine_no: selectedMachineNo.value?.machineno,
      },
    });

    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataModelDie() {
  try {
    const response = await $api("/press-shot/exchange/model-dies", {
      params: {
        machine_no: "",
      },
    });

    modelDieData.value = response.data;

    modelDieData.value.forEach((data) => {
      data.title = data.model + " | " + data.dieno;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataMachineNo() {
  try {
    const response = await $api("/press-shot/exchange/machines-no");

    machineNoData.value = response.data;

    machineNoData.value.forEach((data) => {
      data.title = data.machineno + " | " + data.machinename;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

function formatDateTime(dateString) {
  let momentDate;

  // Check if the date is in numeric format (20241105094958)
  if (/^\d{14}$/.test(dateString)) {
    momentDate = moment(dateString, "YYYYMMDDHHmmss");
  }
  // Check if the date is in YYYY-MM-DD HH:mm:ss format
  else if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateString)) {
    momentDate = moment(dateString, "YYYY-MM-DD HH:mm:ss");
  } else {
    return "Invalid date format";
  }

  moment.locale("id");

  const formattedDate = momentDate.format("D MMMM YYYY");
  const formattedTime = momentDate.format("HH:mm:ss");

  return { formattedDate, formattedTime };
}

function openDetailPage(id) {
  selectedItem.value = id;
  isDetailDialogVisible.value = true;
}

async function openEditPage(id) {
  await router.push({
    path: "/press-shot/exchange-data/exchange-part",
    query: { exchangeid: id },
  });
}

// headers
const headers = [
  {
    title: "EXCHANGE DATE",
    key: "exchangedatetime",
  },
  {
    title: "EMPLOYEE",
    key: "employeecode",
  },
  {
    title: "PART",
    key: "part",
  },
  {
    title: "MACHINE NO",
    key: "machineno",
  },
  {
    title: "MODEL",
    key: "model",
  },
  {
    title: "DIE#",
    key: "dieno",
  },
  {
    title: "DIE UNIT NO#",
    key: "dieunitno",
  },
  {
    title: "PROCESS",
    key: "processname",
  },
  {
    title: "SERIAL NO",
    key: "serialno",
  },
  {
    title: "REASON",
    key: "reason",
  },
  {
    title: "QTTY",
    key: "exchangeqtty",
  },
  {
    title: "SHOT COUNT",
    key: "exchangeshotno",
  },
  {
    title: "ACTIONS",
    key: "actions",
    sortable: false,
  },
];

onMounted(() => {
  fetchData();
  fetchDataModelDie();
  fetchDataMachineNo();
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Press Shot',
          class: 'text-h4',
        },
        {
          title: 'Exchange Data',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VCard class="mb-6">
    <VCardText class="d-flex flex-wrap gap-4">
      <!-- <div class="me-3 d-flex gap-3">
        <AppSelect
          :model-value="itemsPerPage"
          :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
            { value: -1, title: 'All' },
          ]"
          style="inline-size: 6.25rem"
          @update:model-value="itemsPerPage = parseInt($event, 10)"
        />
      </div> -->

      <div style="inline-size: 15.625rem">
        <AppTextField
          v-model="searchQuery"
          placeholder="Search"
          v-on:input="fetchData()"
        />
      </div>

      <VSpacer />
      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <AppDateTimePicker
          style="inline-size: 10rem"
          v-model="date"
          placeholder="Select month"
          :config="{
            dateFormat: 'Y-m',
            mode: 'single',
            enableTime: false,
            enableSeconds: false,
            plugins: [
              new monthSelectPlugin({
                shorthand: true,
                dateFormat: 'Y-m',
                altFormat: 'Y-m',
              }),
            ],
          }"
          append-inner-icon="tabler-calendar"
          @update:modelValue="fetchData()"
        />
      </div>
    </VCardText>
    <VDivider class="mt-4" />
    <VCardText class="d-flex flex-wrap gap-4">
      <AppAutocomplete
        v-model="selectedMachineNo"
        placeholder="Select machine no"
        :items="machineNoData"
        item-title="title"
        return-object
        clearable
        clear-icon="tabler-x"
        outlined
        @update:modelValue="fetchData()"
      />

      <AppAutocomplete
        v-model="selectedModelDie"
        placeholder="Select model die"
        :items="modelDieData"
        item-title="title"
        return-object
        clearable
        clear-icon="tabler-x"
        outlined
        @update:modelValue="fetchData()"
      />

      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <VBtn variant="tonal" prepend-icon="tabler-upload"> Export </VBtn>

        <VBtn variant="tonal" prepend-icon="tabler-list"> Log </VBtn>
      </div>
    </VCardText>

    <VDivider class="mt-4" />

    <VDataTable
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      :items="data"
      :headers="headers"
      class="text-no-wrap"
    >
      <template #item.exchangedatetime="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ formatDateTime(item.exchangedatetime).formattedDate }}</span
            >
            <small>{{
              formatDateTime(item.exchangedatetime).formattedTime
            }}</small>
          </div>
        </div>
      </template>

      <template #item.employeecode="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.employeename ?? "-" }}</span
            >
            <small>{{ item.employeecode ?? "-" }}</small>
          </div>
        </div>
      </template>

      <template #item.part="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.partname ?? "-" }}</span
            >
            <small>{{ item.partcode ?? "-" }}</small>
          </div>
        </div>
      </template>

      <template v-slot:header.dieunitno="{ headers }">
        DIE<br />UNIT NO#
      </template>

      <template v-slot:header.serialno="{ headers }"> SERIAL<br />NO </template>

      <template #item.exchangeshotno="{ item }">
        {{ Intl.NumberFormat().format(item.exchangeshotno) }}
      </template>

      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="openDetailPage(item.exchangedatetime)">
            <VIcon icon="tabler-eye" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <DetailExchangeDataDialog
    v-model:isDialogVisible="isDetailDialogVisible"
    v-model:id="selectedItem"
  />
</template>

<style>
.flatpickr-monthSelect-months {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 10px;
}

.flatpickr-monthSelect-month {
  padding: 10px;
  cursor: pointer;
  text-align: center;
  border-radius: 4px;
}

.flatpickr-monthSelect-month:hover {
  background: #e0e0e0;
}

.flatpickr-monthSelect-month.selected {
  background: #fa0202;
  color: white;
}

.flatpickr-monthSelect-month.flatpickr-disabled {
  color: #999;
  cursor: not-allowed;
  background: #f0f0f0;
}

.flatpickr-monthSelect-month.flatpickr-disabled:hover {
  background: #f0f0f0;
}
</style>
