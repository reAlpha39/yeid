<script setup>
import { useToast } from "vue-toastification";

const toast = useToast();

onMounted(() => {});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Master',
          class: 'text-h4',
        },
        {
          title: 'Part',
          class: 'text-h4',
        },
        {
          title: 'Add New Part',
          class: 'text-h4',
        },
      ]"
    />
  </div>
</template>
