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
const preventionCode = ref();
const preventionName = ref();
const remark = ref();

const isUpdate = ref(false);

async function add() {
  try {
    const result = await $api("/master/preventions", {
      method: "POST",
      body: {
        preventioncode: preventionCode.value,
        preventionname: preventionName.value,
        remark: remark.value,
      },

      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    // console.log(result);
    toast.success("Add kode solution success");
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
      "/master/preventions/" + encodeURIComponent(preventionCode.value),
      {
        method: "PUT",
        body: {
          preventionname: preventionName.value,
          remark: remark.value,
        },

        onResponseError({ response }) {
          // errors.value = response._data.errors;
        },
      }
    );

    // console.log(result);
    toast.success("Update kode solution success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function fetchData(id) {
  try {
    const response = await $api(
      "/master/preventions/" + encodeURIComponent(id),
      {
        onResponseError({ response }) {
          // errors.value = response._data.errors;
        },
      }
    );

    const data = response.data;
    preventionCode.value = data.preventioncode;
    preventionName.value = data.preventionname;
    remark.value = data.remark;
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
      title="Add New Kode Solution"
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
                  v-model="preventionCode"
                  label="Prevention Code"
                  :rules="isUpdate ? [] : [requiredValidator]"
                  placeholder="Input prevention code"
                  maxlength="3"
                  :disabled="isUpdate"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="preventionName"
                  label="Prevention Name"
                  :rules="[requiredValidator]"
                  placeholder="Input prevention name"
                  maxlength="64"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="remark"
                  label="Remark"
                  placeholder="Input remark"
                  maxlength="64"
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
