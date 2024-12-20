<script setup>
import { useToast } from "vue-toastification";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
});

const toast = useToast();
const emit = defineEmits(["update:isDialogVisible"]);

const isUpdate = ref(false);
const refVForm = ref();

const selectedActivity = ref(null);

async function fetchDataActivity(id) {
  try {
    const response = await $api("/schedule/activities/" + id, {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    selectedActivity.value = response.data;

    await fetchDataShop(selectedActivity.shop_id);
  } catch (err) {
    console.log(err);
  }
}

async function fetchDataShop(id) {
  try {
    if (id) {
      const response = await $api("/master/shops/" + encodeURIComponent(id), {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      });

      shopCode.value = response.data;
      shopCode.value.title =
        response.data.shopcode + " | " + response.data.shopname;
    } else {
      const response = await $api("/master/shops", {
        onResponseError({ response }) {
          toast.error(response._data.message);
        },
      });

      shops.value = response.data;

      shops.value.forEach((data) => {
        data.title = data.shopcode + " | " + data.shopname;
      });
    }
  } catch (err) {
    // toast.error("Failed to fetch data");s
    console.log(err);
  }
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      refVForm.value?.reset();
      fetchDataShop();

      if (props.id) {
        fetchDataActivity(props.id);
        isUpdate.value = true;
      } else {
        isUpdate.value = false;
      }
    }
  }
);


</script>

<template></template>
