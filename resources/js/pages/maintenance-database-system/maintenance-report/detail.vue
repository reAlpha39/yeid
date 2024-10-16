<script setup>
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";

const toast = useToast();
const router = useRouter();
const route = useRoute();

const report = ref(null);

async function fetchDataDetail(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/department-requests/" + id
    );
    report.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

onMounted(() => {
  fetchDataDetail(route.query.record_id);
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
</template>
