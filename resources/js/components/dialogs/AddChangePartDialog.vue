<script setup>
import { useToast } from "vue-toastification";
import { VSwitch } from "vuetify/lib/components/index.mjs";

const toast = useToast();

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  item: {
    type: Object,
    required: false,
  },
});

const emit = defineEmits(["update:isDialogVisible", "submit"]);
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];

const refVForm = ref();
const isUpdate = ref(false);

const parts = ref([]);
const selectedPart = ref();
const changePart = ref({
  partcode: undefined,
  partname: undefined,
  specification: undefined,
  brand: undefined,
  qtty: 0,
  price: 0,
  currency: undefined,
  isstock: "1",
});

// Initial state for resetting
const initialChangePart = {
  partcode: undefined,
  partname: undefined,
  specification: undefined,
  brand: undefined,
  qtty: 0,
  price: 0,
  currency: undefined,
  isstock: "1",
};

// Pagination state
const pagination = ref({
  total: 0,
  per_page: 10,
  current_page: 1,
  last_page: 1,
});

// Search state
const search = ref("");
const isLoading = ref(false);

async function submitData() {
  const { valid, errors } = await refVForm.value?.validate();
  if (valid === false) {
    return;
  }
  emit("update:isDialogVisible", false);
  emit("submit", changePart.value);
  resetForm();
}

function handlePartSelection() {
  let val = selectedPart.value;
  if (val === null) return;
  changePart.value = {
    partid: changePart.value.partid,
    partcode: val.partcode,
    partname: val.partname,
    specification: val.specification,
    brand: val.brand,
    qtty: val.qtty,
    price: val.unitprice,
    currency: val.currency,
    isstock: "1",
  };
}

async function fetchPart(id) {
  try {
    if (id) {
      const response = await $api("/master/part", {
        params: {
          part_code: id,
        },
      });

      selectedPart.value = response.data;
      if (selectedPart.value) {
        selectedPart.value.title =
          selectedPart.value.partcode + " | " + selectedPart.value.partname;
      }
    } else {
      isLoading.value = true;
      const response = await $api("/master/part-list", {
        params: {
          page: pagination.value.current_page,
          per_page: pagination.value.per_page,
          search: search.value,
        },
      });

      if (response.success) {
        if (Array.isArray(response.data)) {
          parts.value = response.data;
          parts.value.forEach((data) => {
            data.title = data.partcode + " | " + data.partname;
          });
        }

        if (response.pagination) {
          const { total, per_page, current_page, last_page } =
            response.pagination;
          pagination.value = {
            total: total || 0,
            per_page: per_page || 10,
            current_page: current_page || 1,
            last_page: last_page || 1,
          };
        }
      }
    }
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  } finally {
    isLoading.value = false;
  }
}

// Handle search
const debouncedSearch = computed(() => {
  pagination.value.current_page = 1;
  fetchPart();
});

watch(
  search,
  () => {
    debouncedSearch.value;
  },
  { debounce: 300 }
);

const dialogVisibleUpdate = (val) => {
  if (!val) {
    resetForm();
  }
  emit("update:isDialogVisible", val);
};

const resetForm = () => {
  // Reset all form values
  refVForm.value?.reset();
  selectedPart.value = null;
  search.value = "";
  isUpdate.value = false;

  // Reset changePart to initial state
  changePart.value = { ...initialChangePart };

  // Reset pagination
  pagination.value = {
    total: 0,
    per_page: 10,
    current_page: 1,
    last_page: 1,
  };

  // Clear parts list
  parts.value = [];
};

function isNumber(evt) {
  const keysAllowed = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."];
  const keyPressed = evt.key;

  if (!keysAllowed.includes(keyPressed)) {
    evt.preventDefault();
  }
}

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      fetchPart();
      // console.log("Dialog opened with id:", props.item?.partid);

      if (props.item?.partid) {
        fetchPart(props.item?.partcode);
        changePart.value = { ...props.item };
        isUpdate.value = true;
      } else {
        isUpdate.value = false;
        changePart.value = { ...initialChangePart };
      }
    } else {
      resetForm();
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
    <VForm ref="refVForm" @submit.prevent="submitData">
      <DialogCloseBtn @click="dialogVisibleUpdate(false)" />

      <VCard class="share-project-dialog pa-2 pa-sm-10">
        <VCardText>
          <h4 class="text-h4 text-center">Parts yang Diganti</h4>
        </VCardText>

        <VCardText>
          <p class="text-center">
            Pastikan data yang diinput adalah akurat dan sesuai dengan yang
            sebenarnya.
          </p>
        </VCardText>

        <AppAutocomplete
          class="mb-4"
          v-model="selectedPart"
          label="Kode Part"
          :rules="[requiredValidator]"
          placeholder="Pilih kode part"
          item-title="title"
          :items="parts"
          :loading="isLoading"
          outlined
          return-object
          @update:model-value="handlePartSelection"
        />

        <AppTextField
          v-model="changePart.partname"
          label="Nama Part"
          placeholder="Input nama part"
          maxlength="50"
        />

        <AppTextField
          v-model="changePart.specification"
          label="Spesification"
          placeholder="Input spesifikasi"
          maxlength="50"
        />

        <AppTextField
          v-model="changePart.brand"
          label="Brand"
          placeholder="Masukkan brand"
          maxlength="50"
        />

        <VRow>
          <VCol cols="8">
            <VLabel style="color: #43404f; font-size: 13px">Price</VLabel>
            <div
              style="
                display: flex;
                align-items: center;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
              "
            >
              <VSelect
                v-model="changePart.currency"
                :rules="[requiredValidator]"
                :items="currencies"
                variant="plain"
                style="
                  border-right: 1px solid #e0e0e0;
                  max-width: 80px;
                  padding-bottom: 6px;
                  padding-left: 8px;
                  padding-right: 8px;
                "
              ></VSelect>
              <VTextField
                v-model.number="changePart.price"
                :rules="[requiredValidator]"
                placeholder="0"
                variant="plain"
                type="number"
                style="
                  flex: 1;
                  padding-bottom: 6px;
                  padding-left: 8px;
                  padding-right: 8px;
                  border-radius: 0;
                "
                maxlength="10"
                @keypress="isNumber($event)"
              />
            </div>
          </VCol>
          <VCol cols="4">
            <AppTextField
              v-model.number="changePart.qtty"
              :rules="[requiredValidator]"
              label="Quantity"
              placeholder="0"
              @keypress="isNumber($event)"
              maxlength="5"
            />
          </VCol>
        </VRow>

        <br />

        <VCard variant="flat" style="background-color: #f9f9f9">
          <VCardText>
            <VRow class="d-flex align-center" justify="start">
              <VCol class="d-flex align-center">
                <span> Aktifkan apabila stok masih tersedia </span>
              </VCol>
              <VCol class="d-flex align-center" cols="auto">
                <VSwitch
                  v-model="changePart.isstock"
                  :rules="[requiredValidator]"
                  false-value="0"
                  true-value="1"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <br />

        <VRow>
          <VCol cols="6">
            <div class="d-flex justify-end">
              <VBtn type="submit" color="primary"> Submit </VBtn>
            </div>
          </VCol>

          <VCol cols="6">
            <VBtn
              color="error"
              variant="tonal"
              @click="dialogVisibleUpdate(false)"
            >
              Discard
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VForm>
  </VDialog>
</template>
