<script setup>
import { useToast } from "vue-toastification";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { VForm } from "vuetify/components/VForm";

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  lineCode: {
    type: String,
    required: false,
  },
  shopCode: {
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
const lineCode = ref();
const lineName = ref();

const isUpdate = ref(false);

const shops = ref([]);

async function add() {
  try {
    const result = await $api("/master/lines", {
      method: "POST",
      body: {
        SHOPCODE: shopCode.value,
        LINECODE: lineCode.value,
        LINENAME: lineName.value,
      },
      onResponseError({ response }) {
        toast.error(response._data.error);
      },
    });

    // console.log(result);
    toast.success("Add line success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function update() {
  try {
    const result = await $api(
      "/master/lines/" + shopCode.value + "/" + lineCode.value,
      {
        method: "PUT",
        body: {
          LINENAME: lineName.value,
          UNITPRICE: null,
          TACTTIME: null,
          STAFFNUM: null,
        },

        onResponseError({ response }) {
          toast.error(response._data.error);
        },
      }
    );

    // console.log(result);
    toast.success("Update line success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function fetchData(lineC, shopC) {
  try {
    const response = await $api("/master/lines/" + shopC + "/" + lineC, {
      onResponseError({ response }) {
        toast.error(response._data.error);
      },
    });

    const data = response.data;
    shopCode.value = data.SHOPCODE;
    lineCode.value = data.LINECODE;
    lineName.value = data.LINENAME;
    // console.log(response.data);
  } catch (err) {
    // toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops", {
      onResponseError({ response }) {
        toast.error(response._data.error);
      },
    });

    shops.value = response.data;
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

const resetForm = () => {
  emit("update:isDrawerOpen", false);
  refVForm.value?.reset();
};

watch(
  () => props.isDrawerOpen,
  (newVal) => {
    if (newVal) {
      fetchDataShop();

      refVForm.value?.reset();

      if (props.lineCode) {
        fetchData(props.lineCode, props.shopCode);
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
      title="Add New Line"
      @cancel="$emit('update:isDrawerOpen', false)"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refVForm" @submit.prevent="submitData">
            <VRow>
              <VCol cols="12">
                <AppAutocomplete
                  v-model="shopCode"
                  label="Shop Code"
                  :rules="isUpdate ? [] : [requiredValidator]"
                  placeholder="Select shop"
                  item-title="SHOPCODE"
                  :items="shops"
                  outlined
                  maxlength="4"
                  :readonly="isUpdate"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="lineCode"
                  label="Line Code"
                  :rules="isUpdate ? [] : [requiredValidator]"
                  placeholder="Input line code"
                  maxlength="2"
                  :readonly="isUpdate"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="lineName"
                  label="Line Name"
                  :rules="[requiredValidator]"
                  placeholder="Input line name"
                  maxlength="50"
                />
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
