<script setup>
import { useToast } from "vue-toastification";

const emit = defineEmits(["update:isDialogVisible"]);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
});

const toast = useToast();

const isCurrentPasswordVisible = ref(false);
const isNewPasswordVisible = ref(false);
const isConfirmPasswordVisible = ref(false);

const form = ref();
const currentPassword = ref(null);
const newPassword = ref(null);
const confirmPassword = ref(null);

async function changePassword() {
  const { valid, errors } = await form.value?.validate();
  if (valid === false) {
    return;
  }

  try {
    const data = await $api("/auth/change-password", {
      method: "POST",
      body: {
        current_password: currentPassword.value,
        new_password: newPassword.value,
        new_password_confirmation: confirmPassword.value,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    useCookie("accessToken").value = data.token;

    dialogVisibleUpdate(false);

    toast.success("Change password success");
  } catch (err) {
    console.error(err);
  }
}

const dialogVisibleUpdate = (val) => {
  emit("update:isDialogVisible", val);
};
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :width="$vuetify.display.smAndDown ? 'auto' : 400"
    @update:model-value="dialogVisibleUpdate"
  >
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />
    <VCard class="pa-sm-10">
      <VCardTitle class="mx-2">Change Password</VCardTitle>
      <VForm ref="form" lazy-validation>
        <VCardText>
          <AppTextField
            class="mb-2"
            label="Current Password"
            v-model="currentPassword"
            :rules="[requiredValidator]"
            placeholder="············"
            :type="isCurrentPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="
              isCurrentPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
            "
            @click:append-inner="
              isCurrentPasswordVisible = !isCurrentPasswordVisible
            "
          />
          <AppTextField
            class="mb-2"
            label="New Password"
            v-model="newPassword"
            :rules="[requiredValidator]"
            placeholder="············"
            :type="isNewPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="
              isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
            "
            @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
          />
          <AppTextField
            label="Confirm New Password"
            v-model="confirmPassword"
            :rules="[requiredValidator]"
            placeholder="············"
            :type="isConfirmPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="
              isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
            "
            @click:append-inner="
              isConfirmPasswordVisible = !isConfirmPasswordVisible
            "
          />

          <VBtn @click="changePassword" class="mt-4">Change Password</VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>
