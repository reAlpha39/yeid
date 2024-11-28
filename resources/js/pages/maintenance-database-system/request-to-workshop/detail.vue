<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
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

const data = ref();
const selectedPemohon = ref();
const selectedShop = ref();
const employees = ref([]);

async function fetchDataEmployee(id, isSelectPemohon) {
  try {
    if (id) {
      const response = await $api(
        "/master/employees/" + encodeURIComponent(id)
      );

      let data = response.data;

      if (isSelectPemohon) {
        selectedPemohon.value = data;
        selectedPemohon.value.title = data.employeename;
      }
    } else {
      const response = await $api("/master/employees");

      pemohons.value = response.data;
      pemohons.value.forEach((data) => {
        data.title = data.employeename;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataShop(id) {
  try {
    if (id) {
      const response = await $api("/master/shops/" + encodeURIComponent(id));

      selectedShop.value = response.data;
      selectedShop.value.title =
        response.data.shopcode + " | " + response.data.shopname;
    } else {
      const response = await $api("/master/shops");

      shops.value = response.data;

      shops.value.forEach((data) => {
        data.title = data.shopcode + " | " + data.shopname;
      });
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function revertStaffNames(data) {
  const employeeEntries = data.split("\t");

  employees.value = [];

  for (const entry of employeeEntries) {
    const [employeecode, employeename] = entry.split("|");
    const employeeData = await fetchDataEmployee(employeecode);
    if (employeeData) {
      employees.value.push(employeeData);
    } else {
      employees.value.push({ employeecode, employeename });
    }
  }
}

async function fetchDataEdit(id) {
  try {
    const response = await $api(
      "/maintenance-database-system/request-workshop/" + encodeURIComponent(id)
    );
    data.value = response.data;
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function initEditData(id) {
  await fetchDataEdit(id);

  await fetchDataEmployee(data.value.employeecode, true);
  await fetchDataShop(data.value.shopcode);

  revertStaffNames(data.value.staffnames);
}

function convertAsapFlagId(id) {
  switch (id) {
    case "1":
      return "JIG";
    case "2":
      return "W/S";
    case "3":
      return "FAC";
    default:
      return "";
  }
}

onMounted(() => {
  const id = route.query.wsrid;
  console.log("Fetching data for wsrid:", id);

  initEditData(id);
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
          title: 'Request to Workshop',
          class: 'text-h4',
        },
        {
          title: 'Detail Workshop',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <VCard class="mb-6 px-6 py-4">
    <VCardTitle class="px-6"> Request No. {{ data?.wsrid }} </VCardTitle>

    <VRow class="px-6 py-1" no-gutters>
      <VCol cols="2">
        <text>Request Date : </text>
        <text class="gradient-text">{{ data?.requestdate }} </text>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="6">
        <VRow class="px-6 py-1" no-gutters>
          <VCol cols="4">
            <text> Order title</text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.title }} </text>
          </VCol>
        </VRow>
        <VRow class="px-6 py-1" no-gutters>
          <VCol cols="4">
            <text> Order name</text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.ordername }} </text>
          </VCol>
        </VRow>
        <VRow class="px-6 py-1" no-gutters>
          <VCol cols="4">
            <text> Pemohon</text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.employeename }} </text>
          </VCol>
        </VRow>
        <VRow class="px-6 py-1" no-gutters>
          <VCol cols="4">
            <text> Shop yang dituju </text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.shopcode }} </text>
          </VCol>
        </VRow>
        <VRow class="px-6 py-1" no-gutters>
          <VCol cols="4">
            <text> Reason </text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.reason }} </text>
          </VCol>
        </VRow>
      </VCol>
      <VCol cols="6">
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text> Req. finish date : </text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.reqfinishdate }} </text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text> Delivery place : </text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.deliveryplace }} </text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text> Category : </text>
          </VCol>
          <VCol>
            <text class="gradient-text">
              : {{ convertAsapFlagId(data?.asapflag) }}
            </text>
          </VCol>
        </VRow>
        <VRow class="py-1" no-gutters>
          <VCol cols="4">
            <text> Note : </text>
          </VCol>
          <VCol>
            <text class="gradient-text"> : {{ data?.note }} </text>
          </VCol>
        </VRow>
      </VCol>
    </VRow>
    <br />
  </VCard>

  <VCard class="mb-6">
    <VCardTitle class="mt-3 ml-2 py-4">Staff Name</VCardTitle>

    <VCard variant="outlined" class="mx-4">
      <VCardText
        v-if="employees.length === 0"
        class="my-4 justify-center"
        style="text-align: center"
      >
        Data parts masih kosong. Silakan tambah parts yang ganti.
      </VCardText>
      <div v-else style="overflow-x: auto">
        <VTable class="text-no-wrap" height="250">
          <thead>
            <tr>
              <th style="width: 100px">NO</th>
              <th>STAFF CODE</th>
              <th>STAFF NAME</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item, index) in employees" :key="item.employeecode">
              <td>{{ index + 1 }}</td>
              <td>{{ item.employeecode }}</td>
              <td>{{ item.employeename }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>

    <br />
  </VCard>
</template>

<style scoped>
.gradient-text {
  background: linear-gradient(0deg, #5c4646, #5c4646),
    linear-gradient(0deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1));
  -webkit-background-clip: text; /* For WebKit browsers */
  -webkit-text-fill-color: transparent; /* For WebKit browsers */
  background-clip: text; /* For non-WebKit browsers */
}
</style>
