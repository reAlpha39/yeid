<script setup>
import { ref } from "vue";
import { useToast } from "vue-toastification";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
});

const toast = useToast();
const emit = defineEmits(["update:isDialogVisible", "submit"]);

const isDialogAddActivityVisible = ref(false);
const selectedEditActivityId = ref(null);

const data = ref([]);
const shops = ref([]);
const departments = ref([]);
const selectedDepartment = ref();
const shop = ref();
const search = ref(null);

async function fetchData() {
  try {
    const response = await $api("/schedule/activities", {
      params: {
        activity_name: search.value,
        shop_id: shop.value?.shopcode,
        dept_id: selectedDepartment.value?.id,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    data.value = response.data;

    data.value.forEach((data) => {
      data.title = data.shop_id + " | " + data.activity_name;
    });
  } catch (err) {
    toast.error("Failed to fetch data");
    console.log(err);
  }
}

async function fetchDataShop() {
  try {
    const response = await $api("/master/shops");

    shops.value = response.data;

    shops.value.forEach((data) => {
      data.title = data.shopcode + " | " + data.shopname;
    });
  } catch (err) {
    toast.error("Failed to fetch data shop");
    console.log(err);
  }
}

async function fetchDataDepartment() {
  try {
    const response = await $api("/master/departments", {
      onResponseError({ response }) {
        errors.value = response._data.errors;
      },
    });

    departments.value = response.data;

    departments.value.forEach((data) => {
      data.title = data.code + " | " + data.name;
    });
  } catch (err) {
    toast.error("Failed to fetch department data");
    console.log(err);
  }
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};

async function openEditActivityDialog(id) {
  selectedEditActivityId.value = id;
  isDialogAddActivityVisible.value = true;
}

const handleItemClick = (item) => {
  // Process the item data as needed
  // console.log("Selected item:", item);

  emit("update:isDialogVisible", false);
  emit("submit", item);
};

const debouncedFetchData = debounce(fetchData, 500);

watch(search, () => {
  debouncedFetchData();
});

watch(
  () => props.isDialogVisible,
  (newVal) => {
    if (newVal) {
      fetchData();
      fetchDataShop();
      fetchDataDepartment();
    }
  }
);
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 1200"
    @update:model-value="dialogVisibleUpdate"
  >
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="share-project-dialog pa-2 pa-sm-10">
      <VCardText>
        <VRow>
          <VCol cols="8">
            <h4 class="text-h4 mb-2">Select Schedule Activity</h4>
          </VCol>
          <VCol cols="4" class="d-flex justify-end">
            <VBtn @click="openEditActivityDialog()"> Add New Activity </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VRow class="pb-4">
        <VCol>
          <AppTextField
            v-model="search"
            label="Search"
            placeholder="Search"
            variant="outlined"
          />
        </VCol>

        <VCol>
          <AppAutocomplete
            v-model="shop"
            label="Shop"
            placeholder="Select shop"
            item-title="title"
            :items="shops"
            clear-icon="tabler-x"
            outlined
            return-object
            clearable
            @update:model-value="fetchData()"
          />
        </VCol>
        <VCol>
          <AppAutocomplete
            v-model="selectedDepartment"
            label="Department"
            placeholder="Select deparment"
            item-title="title"
            :items="departments"
            return-object
            outlined
            clearable
            @update:modelValue="fetchData()"
          />
        </VCol>
      </VRow>

      <VDivider />

      <div class="table-container">
        <VTable class="text-no-wrap" height="500">
          <thead>
            <tr>
              <th>Activity Name</th>
              <th>PIC</th>
              <th>Shop</th>

              <th class="text-center" style="width: 120px">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in data" :key="item.id">
              <td>
                {{ item.activity_name }}
              </td>
              <td>
                {{ item.pic?.name }}
              </td>
              <td>
                <div class="d-flex flex-column">
                  <span style="font-weight: 500">{{ item.shop.shopname }}</span>
                  <small>{{ item.shop.shopcode }}</small>
                </div>
              </td>

              <td>
                <div class="d-flex">
                  <VBtn
                    size="small"
                    variant="text"
                    color="primary"
                    @click="openEditActivityDialog(item.activity_id)"
                    density="comfortable"
                  >
                    Edit
                  </VBtn>
                  <VBtn
                    size="small"
                    variant="text"
                    color="primary"
                    class="ml-2"
                    @click="handleItemClick(item)"
                    density="comfortable"
                  >
                    Select
                  </VBtn>
                </div>
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCard>
  </VDialog>

  <AddScheduleActivity
    v-model:isDialogVisible="isDialogAddActivityVisible"
    v-model:id="selectedEditActivityId"
    @submit="fetchData"
  />
</template>
