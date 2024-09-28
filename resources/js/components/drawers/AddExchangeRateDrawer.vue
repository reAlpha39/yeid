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
const year = ref();
const usd2idr = ref();
const jpy2idr = ref();
const eur2idr = ref();
const sgd2idr = ref();

const isUpdate = ref(false);

function updateQuantity(v) {
  let qty = v.value;
  qty = String(qty).replace(/[^\d]/g, "");

  qty = parseInt(qty);

  if (isNaN(qty) || qty < 0) {
    qty = 0;
  }

  // return qty;
  v.value = qty;
}

async function add() {
  try {
    const result = await $api("/master/systems", {
      method: "POST",
      body: {
        YEAR: year.value,
        USD2IDR: usd2idr.value.toString(),
        JPY2IDR: jpy2idr.value.toString(),
        EUR2IDR: eur2idr.value.toString(),
        SGD2IDR: sgd2idr.value.toString(),
      },

      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    // console.log(result);
    toast.success("Add exchange rate success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function update() {
  try {
    const result = await $api("/master/systems/" + year.value, {
      method: "PUT",
      body: {
        USD2IDR: usd2idr.value.toString(),
        JPY2IDR: jpy2idr.value.toString(),
        EUR2IDR: eur2idr.value.toString(),
        SGD2IDR: sgd2idr.value.toString(),
      },

      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    // console.log(result);
    toast.success("Update exchange rate success");
    emit("update:isDrawerOpen", false);
    emit("submit", true);
    refVForm.value?.reset();
  } catch (err) {
    console.log(err);
  }
}

async function fetchData(id) {
  try {
    const response = await $api("/master/systems/" + id, {
      onResponseError({ response }) {
        // errors.value = response._data.errors;
      },
    });

    const data = response.data;
    year.value = data.YEAR;
    usd2idr.value = data.USD2IDR;
    jpy2idr.value = data.JPY2IDR;
    eur2idr.value = data.EUR2IDR;
    sgd2idr.value = data.SGD2IDR;
    // console.log(response.data);
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function submitData() {
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
      title="Add New Exchange Rate"
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
                  v-if="isUpdate"
                  v-model="year"
                  label="Year"
                  :rules="[requiredValidator]"
                  placeholder="Input year"
                  maxlength="4"
                  readonly
                />
                <AppTextField
                  v-else
                  v-model="year"
                  label="Year"
                  :rules="[requiredValidator]"
                  placeholder="Input year"
                  maxlength="4"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model.number="usd2idr"
                  label="USD2IDR"
                  :rules="[requiredValidator]"
                  placeholder="Input rate"
                  maxlength="10"
                  @keypress="isNumber($event)"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model.number="jpy2idr"
                  label="JPY2IDR"
                  :rules="[requiredValidator]"
                  placeholder="Input rate"
                  maxlength="10"
                  @keypress="isNumber($event)"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model.number="eur2idr"
                  label="EUR2IDR"
                  :rules="[requiredValidator]"
                  placeholder="Input rate"
                  maxlength="10"
                  @keypress="isNumber($event)"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model.number="sgd2idr"
                  label="SGD2IDR"
                  :rules="[requiredValidator]"
                  placeholder="Input rate"
                  maxlength="10"
                  @keypress="isNumber($event)"
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
