<script setup>
import { useToast } from "vue-toastification";

const toast = useToast();

const isDeleteDialogVisible = ref(false);

const plants = ["Plant 1", "Plant 2", "Plant 3"];
const shops = ["Shop 1", "Shop 2", "Shop 3"];
const lines = ["Line 1", "Line 2", "Line 3"];
const makers = ["Maker 1", "Maker 2", "Maker 3"];
const statuses = ["Active", "Inactive"];
const ranks = ["Rank 1", "Rank 2", "Rank 3"];
const currencies = ["IDR", "USD", "JPY", "EUR", "SGD"];

const form = ref();

const currency = ref();
const price = ref();
const installDate = ref("");
const installDateMenu = ref(false);

async function addData() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }
}

onMounted(() => {
  // fetchData();
});
</script>

<template>
  <VForm ref="form" lazy-validation>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Master',
          class: 'text-h4',
        },
        {
          title: 'Machine',
          class: 'text-h4',
        },
        {
          title: 'Add New Machine',
          class: 'text-h4',
        },
      ]"
    />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              label="Machine No"
              :rules="[requiredValidator]"
              placeholder="Input machine no"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField
              label="Machine Name"
              :rules="[requiredValidator]"
              placeholder="Input machine name"
              outlined
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="3">
            <AppSelect
              label="Plant No"
              :rules="[requiredValidator]"
              placeholder="Select plant no"
              :items="plants"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              label="Shop"
              :rules="[requiredValidator]"
              placeholder="Select shop"
              :items="shops"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              label="Line No"
              :rules="[requiredValidator]"
              placeholder="Select line no"
              :items="lines"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField label="Model" placeholder="Input model" outlined />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="3">
            <AppSelect
              label="Maker"
              :rules="[requiredValidator]"
              placeholder="Select maker"
              :items="makers"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppTextField
              label="Serial No"
              :rules="[requiredValidator]"
              placeholder="Input serial no"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <VRow align="center">
              <VCol cols="12">
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
                    v-model="currency"
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
                    v-model="price"
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
                  ></VTextField>
                </div>
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="12" sm="3">
            <AppDateTimePicker
              v-model="installDate"
              :rules="[requiredValidator]"
              label="Install Date"
              placeholder="31/01/2024"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="6">
            <AppTextField
              label="Purchase Root"
              :rules="[requiredValidator]"
              placeholder="Input purchase root"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppTextField label="Note" placeholder="Input note" outlined />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" sm="3">
            <AppSelect
              label="Status"
              :rules="[requiredValidator]"
              placeholder="Select status"
              :items="statuses"
              outlined
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              label="Rank"
              :rules="[requiredValidator]"
              placeholder="Select rank"
              :items="ranks"
              outlined
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow class="d-flex justify-start py-8">
      <VCol>
        <VBtn color="success" class="me-4" @click="addData">Save</VBtn>
        <VBtn variant="outlined" color="error" to="/master/machine"
          >Cancel</VBtn
        >
      </VCol>
    </VRow>
  </VForm>
</template>
