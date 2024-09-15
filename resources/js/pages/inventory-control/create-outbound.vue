<script setup>
import { ref } from 'vue';

const parts = ref([
  {
    id: 1,
    part: 'MAGNETIC CONTACTOR SI-M0121',
    brand: 'MITSUBISHI',
    specification: 'S-T 10 200V 1A',
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
    note: ''
  },
  {
    id: 2,
    part: 'MAGNETIC CONTACTOR SI-M0121',
    brand: 'MITSUBISHI',
    specification: 'S-T 10 200V 1A',
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
    note: ''
  },
  {
    id: 3,
    part: 'MAGNETIC CONTACTOR SI-M0121',
    brand: 'MITSUBISHI',
    specification: 'S-T 10 200V 1A',
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
    note: ''
  }
]);

const addPart = () => {
  parts.value.push({
    id: parts.value.length + 1,
    part: 'MAGNETIC CONTACTOR SI-M0121',
    brand: 'MITSUBISHI',
    specification: 'S-T 10 200V 1A',
    unitPrice: 190000,
    qty: 1,
    totalPrice: 190000,
    note: ''
  });
};

const removePart = (id) => {
  parts.value = parts.value.filter(part => part.id !== id);
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(value).replace('IDR', 'Rp');
};
</script>

<template>
  <VCard class="pa-6">
    <VCardTitle class="text-h6 mb-4">List Part</VCardTitle>
    <VTable class="mb-4">
      <thead>
        <tr>
          <th class="text-left">PART</th>
          <th class="text-left">BRAND</th>
          <th class="text-left">SPECIFICATION</th>
          <th class="text-right">UNIT PRICE</th>
          <th class="text-center">QTY</th>
          <th class="text-right">TOTAL PRICE</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="part in parts" :key="part.id">
          <td>{{ part.part }}</td>
          <td>{{ part.brand }}</td>
          <td>{{ part.specification }}</td>
          <td class="text-right">{{ formatCurrency(part.unitPrice) }}</td>
          <td class="text-center">
            <VTextField
              v-model="part.qty"
              type="number"
              min="1"
              density="compact"
              hide-details
              class="quantity-input"
            ></VTextField>
          </td>
          <td class="text-right">{{ formatCurrency(part.totalPrice) }}</td>
          <td>
            <VBtn icon color="primary" variant="text" @click="removePart(part.id)">
              <VIcon>mdi-delete</VIcon>
            </VBtn>
          </td>
        </tr>
      </tbody>
    </VTable>
    <div class="notes-section">
      <VTextField
        v-for="part in parts"
        :key="`note-${part.id}`"
        v-model="part.note"
        label="Note"
        variant="outlined"
        density="compact"
        hide-details
        class="mb-2"
      ></VTextField>
    </div>
    <VBtn color="primary" class="mt-4" @click="addPart">Add Part</VBtn>
  </VCard>
</template>

<style scoped>
.quantity-input {
  width: 60px;
  margin: 0 auto;
}
.notes-section {
  margin-bottom: 16px;
}
</style>
