<script setup>
import { useDropZone, useFileDialog, useObjectUrl } from "@vueuse/core";

const props = defineProps({
  existingImage: {
    type: String,
    default: null,
  },
  modelValue: null,
});

const dropZoneRef = ref();
const fileData = ref([]);
const { open, onChange } = useFileDialog({ accept: "image/jpeg,image/png" });

// Emits for v-model and remove event
const emit = defineEmits(["update:modelValue", "remove"]);

// Watch for changes in existingImage prop
watch(
  () => props.existingImage,
  (newVal) => {
    if (newVal) {
      fileData.value = [
        {
          isExisting: true,
          url: newVal,
          name: newVal.split("/").pop() || "Existing Image",
          size: null,
        },
      ];
    } else {
      fileData.value = [];
    }
  },
  { immediate: true }
);

function onDrop(DroppedFiles) {
  DroppedFiles?.forEach((file) => {
    if (file.type.slice(0, 6) !== "image/") {
      alert("Only image files are allowed");
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      alert("File size must be less than 2MB");
      return;
    }
    fileData.value = [
      {
        file,
        url: useObjectUrl(file).value ?? "",
        name: file.name,
        size: file.size,
        isExisting: false,
      },
    ];
    emit("update:modelValue", file);
  });
}

onChange((selectedFiles) => {
  if (!selectedFiles) return;
  const file = selectedFiles[0];
  if (file.size > 2 * 1024 * 1024) {
    alert("File size must be less than 2MB");
    return;
  }
  fileData.value = [
    {
      file,
      url: useObjectUrl(file).value ?? "",
      name: file.name,
      size: file.size,
      isExisting: false,
    },
  ];
  emit("update:modelValue", file);
});

function removeFile(index) {
  const removedFile = fileData.value[index];
  fileData.value = [];
  emit("update:modelValue", null);

  // Emit remove event when removing an existing image
  if (removedFile.isExisting) {
    emit("remove");
  }
}

useDropZone(dropZoneRef, onDrop);
</script>

<template>
  <div class="flex">
    <div class="w-full h-auto relative">
      <div ref="dropZoneRef" class="cursor-pointer" @click="() => open()">
        <div
          v-if="fileData.length === 0"
          class="d-flex flex-column justify-center align-center gap-y-2 pa-12 drop-zone rounded"
        >
          <IconBtn variant="tonal" class="rounded-sm">
            <VIcon icon="tabler-upload" />
          </IconBtn>
          <h4 class="text-h4">Drag and drop your image here.</h4>
          <span class="text-disabled">or</span>
          <VBtn variant="tonal" size="small"> Browse Images </VBtn>
          <div class="text-caption text-disabled mt-2">
            Maximum file size: 2MB, Format ( jpg / jpeg / png )
          </div>
        </div>

        <div v-else class="justify-center align-center">
          <template v-for="(item, index) in fileData" :key="index">
            <VCard
              class="border-dashed"
              :ripple="false"
              variant="outlined"
              border
            >
              <VRow class="d-flex align-center">
                <VCol>
                  <VCardText @click.stop>
                    <div class="mt-2">
                      <span class="clamp-text text-wrap text-h5">
                        {{ item.name }}
                      </span>
                      <span v-if="item.size">
                        {{ (item.size / 1024).toFixed(2) }} KB
                      </span>
                      <span v-else class="text-caption">Existing image</span>
                    </div>
                  </VCardText>
                </VCol>
                <VCol
                  cols="auto"
                  class="d-flex align-center justify-center mt-4"
                >
                  <VCardActions>
                    <VBtn variant="text" block @click.stop="removeFile(index)">
                      Remove File
                    </VBtn>
                  </VCardActions>
                </VCol>
              </VRow>
            </VCard>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}
</style>
