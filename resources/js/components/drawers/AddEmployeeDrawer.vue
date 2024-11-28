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
const employeeCode = ref();
const employeeName = ref();
const mlevel = ref();
const password = ref();

const isUpdate = ref(false);

async function add() {
  try {
    const result = await $api("/master/employees", {
      method: "POST",
      body: {
        employeecode: employeeCode.value,
        employeename: employeeName.value,
        mlevel: mlevel.value.toString(),
        password: password.value,
      },

      onResponseError({ response }) {
        // errors.value = response._data.errors;
        toast.error(response._data.error);
      },
    });

    // console.log(result);
    toast.success("Add employee success");
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
      "/master/employees/" + encodeURIComponent(employeeCode.value),
      {
        method: "PUT",
        body: {
          employeename: employeeName.value,
          mlevel: mlevel.value.toString(),
          password: password.value,
        },

        onResponseError({ response }) {
          toast.error(response._data.error);
        },
      }
    );

    // console.log(result);
    toast.success("Update employee success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function fetchData(id) {
  try {
    const response = await $api("/master/employees/" + encodeURIComponent(id), {
      onResponseError({ response }) {
        toast.error(response._data.error);
      },
    });

    const data = response.data;
    employeeCode.value = data.employeecode;
    employeeName.value = data.employeename;
    mlevel.value = data.mlevel;
    password.value = data.password;
    // console.log(response.data);
  } catch (err) {
    // toast.error("Failed to fetch data");
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

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

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
      title="Add New Employee"
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
                  v-model="employeeCode"
                  label="Employee Code"
                  :rules="isUpdate ? [] : [requiredValidator]"
                  placeholder="Input employee code"
                  maxlength="8"
                  :disabled="isUpdate"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="employeeName"
                  label="Employee Name"
                  :rules="[requiredValidator]"
                  placeholder="Input employee name"
                  maxlength="30"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="mlevel"
                  label="mlevel"
                  :rules="[
                    requiredValidator,
                    (v) =>
                      !v ||
                      (parseInt(v) >= 1 && parseInt(v) <= 3) ||
                      'Level must be between 1 and 3',
                  ]"
                  placeholder="Input mlevel"
                  maxlength="1"
                  @keypress="isNumber($event)"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  label="Password"
                  :rules="[requiredValidator]"
                  placeholder="Input password"
                  maxlength="20"
                  type="password"
                  autocomplete="new-password"
                  @copy.prevent
                  @paste.prevent
                  @cut.prevent
                  @contextmenu.prevent
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
