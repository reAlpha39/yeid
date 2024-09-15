<script setup>
// import data from '@/views/demos/forms/tables/data-table/datatable';

// No need to repeat `https://localhost/api` now
const data = await $api("/invControl", {
  params: {
    startDate: "20240417",
    endDate: "20241231",
    jobCode: "I",
    limit: 0,
    orderBy: "jobdate",
    direction: "desc",
  },
});

const deleteDialog = ref(false);

const defaultItem = ref({
  responsiveId: "",
  id: -1,
  avatar: "",
  fullName: "",
  post: "",
  email: "",
  city: "",
  startDate: "",
  salary: -1,
  age: "",
  experience: "",
  status: -1,
});

const editedItem = ref(defaultItem.value);
const editedIndex = ref(-1);
const userList = ref([]);

// headers
const headers = [
  {
    title: "PART",
    key: "partcode",
  },
  {
    title: "DATE",
    key: "jobdate",
  },
  {
    title: "VENDOR",
    key: "vendor",
  },
  {
    title: "UNIT PRICE",
    key: "currency",
  },
  {
    title: "QTY",
    key: "quantity",
  },
  {
    title: "TOTAL PRICE",
    key: "total",
  },
  {
    title: "ACTIONS",
    key: "actions",
  },
];

const deleteItem = (item) => {
  editedIndex.value = userList.value.indexOf(item);
  editedItem.value = { ...item };
  deleteDialog.value = true;
};

const close = () => {
  editedIndex.value = -1;
  editedItem.value = { ...defaultItem.value };
};

const closeDelete = () => {
  deleteDialog.value = false;
  editedIndex.value = -1;
  editedItem.value = { ...defaultItem.value };
};

const save = () => {
  if (editedIndex.value > -1)
    Object.assign(userList.value[editedIndex.value], editedItem.value);
  else userList.value.push(editedItem.value);
  close();
};

const deleteItemConfirm = () => {
  userList.value.splice(editedIndex.value, 1);
  closeDelete();
};

onMounted(() => {
  userList.value = JSON.parse(JSON.stringify(data));
});
</script>

<template>
  <VCard title="Search Filter">
    <div
      class="d-flex justify-space-between align-center ms-3"
      style="gap: 10px; padding-right: 16px"
    >
      <div class="d-flex align-center" style="gap: 10px">
        <!-- Dropdown (10 dropdown) -->
        <v-select
          :items="[10, 20, 30, 40]"
          v-model="selected"
          dense
          outlined
          class="pa-0"
          style="max-width: 80px"
        />
      </div>

      <div class="d-flex align-center" style="gap: 10px">
        <!-- Search Input -->
        <AppTextField
          placeholder="Search"
          label=""
          clearable
          class="pa-0"
          style="min-width: 200px"
        />

        <VBtn outlined color="error" class="d-flex align-center">
          <i class="tabler-export"></i>
          <span>Export</span>
        </VBtn>

        <VBtn
          color="primary"
          class="d-flex flex-column align-center justify-center"
        >
          <i class="tabler-plus" />
          <span>Create In-Bound</span>
        </VBtn>
      </div>
    </div>

    <!-- 👉 Datatable  -->
    <VDataTable :headers="headers" :items="data" :items-per-page="10">
      <!-- part name -->
      <template #item.partcode="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column ms-3">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.partname }}</span
            >
            <small>{{ item.partcode }}</small>
          </div>
        </div>
      </template>

      <!-- date -->
      <template #item.date="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column ms-3">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.brand }}</span
            >
            <small>{{ item.vendorcode }}</small>
          </div>
        </div>
      </template>

      <!-- vendor -->
      <template #item.vendor="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column ms-3">
            <span
              class="d-block font-weight-medium text-high-emphasis text-truncate"
              >{{ item.brand }}</span
            >
            <small>{{ item.vendorcode }}</small>
          </div>
        </div>
      </template>

      <!-- unit price -->
      <template #item.currency="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.currency }}
            {{ item.unitprice }}
          </div>
        </div>
      </template>

      <!-- unit price -->
      <template #item.total="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-row ms-3">
            {{ item.currency }}
            {{ item.total }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="align-center">
          <IconBtn @click="deleteItem(item)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </div>
      </template>
    </VDataTable>
  </VCard>

  <!-- 👉 Delete Dialog  -->
  <VDialog v-model="deleteDialog" max-width="500px">
    <VCard>
      <VCardTitle> Are you sure you want to delete this item? </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn color="error" variant="outlined" @click="closeDelete">
          Cancel
        </VBtn>

        <VBtn color="success" variant="elevated" @click="deleteItemConfirm">
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<!-- <script>
export default {
    name:"invControl",
    data(){
        return {
            invControl:[]
        }
    },
    // mounted(){
    //     this.getCategories()
    // },
    methods:{
        async getCategories(){
            await this.$axios.get('/api/invControl').then(response=>{
                this.categories = response.data
            }).catch(error=>{
                console.log(error)
                this.invControl = []
            })
        },
        deleteCategory(id){
            if(confirm("Are you sure to delete this category ?")){
                this.axios.delete(`/api/category/${id}`).then(response=>{
                    this.getCategories()
                }).catch(error=>{
                    console.log(error)
                })
            }
        }
    }
}
</script> -->
