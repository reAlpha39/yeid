<script setup>
import { useToast } from "vue-toastification";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  id: {
    type: Number,
    required: false,
  },
});

const toast = useToast();
const emit = defineEmits(["update:isDialogVisible", "submit"]);

const isUpdate = ref(false);
const refVForm = ref();
const shops = ref([]);

const activityTitle = ref(null);
const shopCode = ref(null);
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

async function submitData() {
  const { valid, errors } = await refVForm.value?.validate();
  if (valid === false) {
    return;
  }

  if (isUpdate.value) {
    await update();
  } else {
    await add();
  }
}

async function add() {
  try {
    await $api("/schedule/activities", {
      method: "POST",
      body: {
        shop_id: shopCode.value.shopcode,
        activity_name: activityTitle.value,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    toast.success("Data added successfully");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    console.log(err);
  }
}

async function update() {
  try {
    await $api("/schedule/activities/" + selectedActivity.value.activity_id, {
      method: "PUT",
      body: {
        shop_id: shopCode.value.shopcode,
        activity_name: activityTitle.value,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    toast.success("Data updated successfully");
    emit("update:isDialogVisible", false);
    emit("submit", true);
  } catch (err) {
    console.log(err);
  }
}

async function applyData() {
  activityTitle.value = selectedActivity.value.activity_name;
  await fetchDataShop(selectedActivity.value.shop_id);
}

function resetForm() {
  emit("update:isDialogVisible", false);
  activityTitle.value = null;
  shopCode.value = null;
  refVForm.value?.reset();
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

watch(
  () => props.isDialogVisible,
  async (newVal) => {
    if (newVal) {
      refVForm.value?.reset();
      await fetchDataShop();

      if (props.id) {
        await fetchDataActivity(props.id);
        applyData();
        isUpdate.value = true;
      } else {
        isUpdate.value = false;
      }
    }
  }
);
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 800"
    @update:model-value="dialogVisibleUpdate"
  >
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VForm ref="refVForm" @submit.prevent="submitData">
      <VCard>
        <VCardText>
          <VRow>
            <VCol cols="8">
              <h4 v-if="isUpdate" class="text-h4 mb-2">
                Edit Schedule Activity
              </h4>
              <h4 v-else class="text-h4 mb-2">Add Schedule Activity</h4>
            </VCol>
          </VRow>
        </VCardText>

        <VRow class="pb-4 px-6">
          <VCol cols="6">
            <AppAutocomplete
              v-model="shopCode"
              label="Shop"
              placeholder="Select shop"
              item-title="title"
              :items="shops"
              clear-icon="tabler-x"
              outlined
              return-object
              clearable
              :rules="isUpdate ? [] : [requiredValidator]"
              :disabled="isUpdate"
            />
          </VCol>

          <VCol cols="6">
            <AppTextField
              v-model="activityTitle"
              label="Schedule Activity"
              placeholder="input schedule activity"
              variant="outlined"
              :rules="[requiredValidator]"
              maxlength="255"
            />
          </VCol>
        </VRow>

        <VRow class="mb-6">
          <VCol cols="6">
            <div class="d-flex justify-end">
              <VBtn type="submit" color="success"> Submit </VBtn>
            </div>
          </VCol>

          <VCol cols="6">
            <VBtn color="error" variant="tonal" @click="resetForm">
              Discard
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VForm>
  </VDialog>
</template>
