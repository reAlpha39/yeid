<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

definePage({
  meta: {
    action: "view",
    subject: "maintenanceReport",
  },
});

const toast = useToast();
const router = useRouter();
const route = useRoute();

const report = ref(null);
const totalWorkTime = ref(0);
const totalPartCost = ref(0);
const addedWorkTime = ref([]);
const addedChangedPart = ref([]);

async function fetchDataDetail(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests/" +
        encodeURIComponent(id)
    );
    report.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchWorks(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/work/" + encodeURIComponent(id)
    );

    addedWorkTime.value = response.data;
  } catch (err) {
    if (!(err.response && err.response.status === 404)) {
      toast.error("Failed to fetch data");
      console.log(err);
    }
  }
}

async function fetchParts(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/part/" + encodeURIComponent(id)
    );

    addedChangedPart.value = response.data;
  } catch (err) {
    if (!(err.response && err.response.status === 404)) {
      toast.error("Failed to fetch data");
      console.log(err);
    }
  }
}

async function initData(id) {
  await fetchDataDetail(id);

  const data = report.value;
  if (data.totalrepairsum != "0") {
    await fetchWorks(id);
  }
  if (data.partcostsum != "0") {
    await fetchParts(id);
  }
}

let idr = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
});

onMounted(() => {
  initData(route.query.record_id);
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Maintenance Database System',
          class: 'text-h4',
        },
        {
          title: 'Maintenance Report',
          class: 'text-h4',
        },
        {
          title: 'Detail',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VCard>
    <VCardTitle class="mt-2 mx-2">
      Nomor SPK : {{ report?.recordid }}
    </VCardTitle>

    <VRow class="px-6 py-4" no-gutters>
      <VCol cols="6">
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Keadaan</text>
          </VCol>
          <VCol cols="4">
            <text>: -</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Order Shop</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.ordershop }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Nama Maker</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.makername }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Stop Panjang</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.ltfactor }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Uraian Masalah</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.situation }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Penyebab</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.factor }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Temporary Tindakan</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.measure }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Solution</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.prevention }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Name</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.orderempname }}</text>
          </VCol>
        </VRow>
      </VCol>
      <VCol cols="6">
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Kode</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.maintenancecode }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Mengapa dan<br />Bagaimana</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.ordertitle }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Kode S.P</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.ltfactorcode }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Kode U.M</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.situationcode }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Kode P</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.factorcode }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Kode T.T</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.measurecode }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Kode S</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.preventioncode }}</text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text>Komentar</text>
          </VCol>
          <VCol cols="4">
            <text>: {{ report?.comments }}</text>
          </VCol>
        </VRow>
      </VCol>
    </VRow>
  </VCard>

  <br />

  <VCard>
    <VCardTitle class="my-3">Detail untuk waktu kerjakan</VCardTitle>

    <VCard
      variant="outlined"
      class="mx-4 px-4 py-2"
      style="background-color: #f9f9f9; width: auto; display: inline-block"
    >
      <text style="text-align: center">
        Total = {{ report?.totalrepairsum }} Menit
      </text>
    </VCard>

    <VCard variant="outlined" class="mx-4">
      <VCardText
        v-if="addedWorkTime.length === 0"
        class="my-4 justify-center"
        style="text-align: center"
      >
        Data pekerjaan maintenance masih kosong. Silakan tambah jadwal pekerjaan
        maintenance.
      </VCardText>
      <div v-else style="overflow-x: auto">
        <VTable class="text-no-wrap" height="250">
          <thead>
            <tr>
              <th>NO</th>
              <th>NAME</th>
              <th>WAKTU<br />SEBELUM</th>
              <th>WAKTU<br />PERIODICAL</th>
              <th>WAKTU<br />PERTANYAAN</th>
              <th>WAKTU<br />SIAPKAN</th>
              <th>WAKTU<br />PENELITIAN</th>
              <th>WAKTU<br />MENUNGGU PART</th>
              <th>WAKTU PEKERJAAN<br />MAINTENANCE</th>
              <th>WAKTU<br />KONFIRMASI</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in addedWorkTime" :key="item.workid">
              <td>{{ item.workid }}</td>
              <td>{{ item.staffname }}</td>
              <td>{{ item.inactivetime }}</td>
              <td>{{ item.periodicaltime }}</td>
              <td>{{ item.questiontime }}</td>
              <td>{{ item.preparetime }}</td>
              <td>{{ item.checktime }}</td>
              <td>{{ item.waittime }}</td>
              <td>{{ item.repairtime }}</td>
              <td>{{ item.confirmtime }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>

    <br />
  </VCard>

  <br />

  <VCard>
    <VCardTitle class="my-3">Detail untuk ganti part</VCardTitle>

    <VCard
      variant="outlined"
      class="mx-4 px-4 py-2"
      style="background-color: #f9f9f9; width: auto; display: inline-block"
    >
      <text style="text-align: center">
        Total = {{ idr.format(parseFloat(report?.partcostsum)) }}
      </text>
    </VCard>

    <VCard variant="outlined" class="mx-4">
      <VCardText
        v-if="addedChangedPart.length === 0"
        class="my-4 justify-center"
        style="text-align: center"
      >
        Data parts masih kosong. Silakan tambah parts yang ganti.
      </VCardText>
      <div v-else style="overflow-x: auto">
        <VTable class="text-no-wrap" height="250">
          <thead>
            <tr>
              <th>NO</th>
              <th>PART</th>
              <th>SPESIFIKASI</th>
              <th>BRAND</th>
              <th>QUANTITY</th>
              <th>HARGA</th>
              <th>CURRENCY</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in addedChangedPart" :key="item.partid">
              <td>{{ item.partid }}</td>
              <td>
                {{ item.partname }} <br />
                <small>{{ item.partcode }}</small>
              </td>
              <td>{{ item.specification }}</td>
              <td>{{ item.brand }}</td>
              <td>{{ item.qtty }}</td>
              <td>{{ item.price }}</td>
              <td>{{ item.currency }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>

    <br />
  </VCard>
</template>
