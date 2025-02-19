<script setup>
definePage({
  meta: {
    action: "view",
    subject: "mtDbsDeptReq",
  },
});

const currentTab = ref("window1");

const user = ref();
const isLoading = ref(false);

async function fetchUser() {
  try {
    const response = await $api("/auth/user", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    user.value = response.data;

    console.log(response.data);
  } catch (err) {
    console.log(err);
  }
}

onMounted(async () => {
  isLoading.value = true;
  await fetchUser();
  isLoading.value = false;
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
          title: 'Department Request',
          class: 'text-h4',
        },
      ]"
    />
  </div>

  <div
    v-if="isLoading"
    class="d-flex justify-center align-center"
    style="min-height: 200px"
  >
    <VProgressCircular indeterminate color="primary" />
  </div>

  <div v-else>
    <div class="d-flex flex-wrap gap-4 mb-4">
      <VTabs v-model="currentTab" class="v-tabs-pill">
        <VTab>All</VTab>
        <VTab v-if="user?.role_access === '2' || user?.role_access === '3'"
          >Need Approval</VTab
        >
      </VTabs>
    </div>

    <VWindow v-model="currentTab">
      <VWindowItem key="`window1`">
        <MaintenanceDeptReqAllTab />
      </VWindowItem>
      <VWindowItem key="`window2`">
        <MaintenanceDeptReqApprovalTab />
      </VWindowItem>
    </VWindow>
  </div>
</template>
