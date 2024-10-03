<script setup>
import { useToast } from "vue-toastification";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { VForm } from "vuetify/components/VForm";

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  id: {
    type: String,
    required: false,
  },
});

const emit = defineEmits(["update:isDrawerOpen", "submit"]);

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};

const toast = useToast();

const refVForm = ref();
const shopCode = ref();
const shopName = ref();
const countFlag = ref(true);

const isUpdate = ref(false);

async function addShop() {
  try {
    const result = await $api("/master/shops", {
      method: "POST",
      body: {
        SHOPCODE: shopCode.value,
        SHOPNAME: shopName.value,
        PLANTTYPE: "M",
        COUNTFLAG: countFlag.value ? "1" : "0",
      },

      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    // console.log(result);
    toast.success("Add shop success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function updateShop() {
  try {
    const result = await $api("/master/shops/" + shopCode.value, {
      method: "PUT",
      body: {
        SHOPNAME: shopName.value,
        PLANTTYPE: "M",
        COUNTFLAG: countFlag.value ? "1" : "0",
      },

      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    // console.log(result);
    toast.success("Add shop success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function fetchData(id) {
  try {
    const response = await $api("/master/shops/" + id, {
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    const data = response.data;
    shopCode.value = data.SHOPCODE;
    shopName.value = data.SHOPNAME;
    countFlag.value = data.COUNTFLAG == "1" ? true : false;
    // console.log(response.data);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function submitData() {
  const { valid, errors } = await refVForm.value?.validate();
  if (valid === false) {
    return;
  }

  if (isUpdate.value) {
    await updateShop();
  } else {
    await addShop();
  }
}

const resetForm = () => {
  emit("update:isDrawerOpen", false);
  refVForm.value?.reset();
};

watch(
  () => props.isDrawerOpen,
  (newVal) => {
    if (newVal) {
      refVForm.value?.reset();
      console.log("Drawer opened with id:", props.id); // Print the id when dialog opens
      if (props.id) {
        fetchData(props.id);
        isUpdate.value = true;
      } else {
        isUpdate.value = false;
      }
    }
  }
);
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    temporary
    location="end"
    width="370"
    border="none"
    class="category-navigation-drawer scrollable-content"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Header -->
    <AppDrawerHeaderSection
      title="Add New Shop"
      @cancel="$emit('update:isDrawerOpen', false)"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refVForm" @submit.prevent="submitData">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="shopCode"
                  label="Shop Code"
                  :rules="isUpdate ? [] : [requiredValidator]"
                  placeholder="Input shop code"
                  maxlength="4"
                  :readonly="isUpdate"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="shopName"
                  label="Shop Name"
                  :rules="[requiredValidator]"
                  placeholder="Input shop name"
                  maxlength="20"
                />
              </VCol>

              <VCol cols="12">
                <VCheckbox v-model="countFlag" label="Count Flag" />
              </VCol>

              <VCol cols="12">
                <div class="d-flex justify-start">
                  <VBtn type="submit" color="primary" class="me-4"> Add </VBtn>
                  <VBtn color="error" variant="tonal" @click="resetForm">
                    Discard
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style lang="scss">
.category-navigation-drawer {
  .ProseMirror {
    min-block-size: 9vh !important;

    p {
      margin-block-end: 0;
    }

    p.is-editor-empty:first-child::before {
      block-size: 0;
      color: #adb5bd;
      content: attr(data-placeholder);
      float: inline-start;
      pointer-events: none;
    }

    &-focused {
      outline: none;
    }

    ul,
    ol {
      padding-inline: 1.125rem;
    }
  }

  .is-active {
    border-color: rgba(
      var(--v-theme-primary),
      var(--v-border-opacity)
    ) !important;
    background-color: rgba(var(--v-theme-primary), var(--v-activated-opacity));
    color: rgb(var(--v-theme-primary));
  }
}
</style>
