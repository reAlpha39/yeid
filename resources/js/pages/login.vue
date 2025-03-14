<!-- ❗Errors in the form are set on line 60 -->
<script setup>
import { useAbility } from "@casl/vue";
import authLoginIllustration from "@images/pages/auth_login_illustration.png";
import logoImage from "@images/pages/logo.png";
import { useToast } from "vue-toastification";
import { VForm } from "vuetify/components/VForm";
import { VImg } from "vuetify/lib/components/index.mjs";

definePage({
  meta: {
    layout: "blank",
    unauthenticatedOnly: true,
  },
});

const isPasswordVisible = ref(false);
const route = useRoute();
const router = useRouter();
const toast = useToast();
const ability = useAbility();
const valid = ref(false);

const errors = ref({
  nik: undefined,
  email: undefined,
  password: undefined,
});

const refVForm = ref();

const credentials = ref({
  nik: "",
  email: "",
  password: "",
});

const rememberMe = ref(false);

const login = async () => {
  try {
    const data = await $api("/auth/login", {
      method: "POST",
      body: {
        nik: credentials.value.nik,
        // email: credentials.value.email,
        password: credentials.value.password,
        remember_me: rememberMe.value,
      },
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    const { token, user } = data;

    const caslPermissions = convertPermissions(user.control_access);

    localStorage.setItem("userAbilityRules", JSON.stringify(caslPermissions));
    ability.update(caslPermissions);

    // remove control_access from user data
    const { control_access, ...userWithoutAccess } = user;

    useCookie("userData").value = userWithoutAccess;
    useCookie("accessToken").value = token;
    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : "/");
    });
  } catch (err) {
    console.error(err);
  }
};

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) login();
  });
};
</script>

<template>
  <div class="login-wrapper">
    <div class="background-container">
      <VImg :src="authLoginIllustration" class="background-image" cover />
    </div>

    <VContainer
      class="d-flex align-center justify-center"
      style="min-height: 100vh"
    >
      <div class="d-flex flex-column align-center" style="max-width: 500px">
        <div class="logo-container text-center mb-4">
          <RouterLink to="/">
            <VImg :src="logoImage" :width="200" class="logo-image" contain />
          </RouterLink>
        </div>
        <h2 class="text-center mb-2">
          Welcome to Yamaha Electronic Motor Indonesia
        </h2>
        <p class="text-center mb-6">
          Please sign in to your account using your NIK and password.
        </p>
        <VCard class="login-card pa-8 w-100" elevation="4" rounded="lg">
          <!-- Login Form -->
          <VForm ref="refVForm" v-model="valid" @submit.prevent="onSubmit">
            <!-- <VTextField
              v-model="credentials.email"
              label="Email"
              placeholder="Input email"
              variant="outlined"
              :rules="[requiredValidator, emailValidator]"
              :error-messages="errors.email"
              class="mb-4"
            /> -->

            <VTextField
              v-model="credentials.nik"
              label="NPK"
              placeholder="Input NPK"
              variant="outlined"
              :rules="[requiredValidator]"
              :error-messages="errors.nik"
              class="mb-4"
            />

            <VTextField
              v-model="credentials.password"
              label="Password"
              placeholder="Input password"
              variant="outlined"
              :rules="[requiredValidator]"
              :error-messages="errors.password"
              :type="isPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="
                isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
              "
              @click:append-inner="isPasswordVisible = !isPasswordVisible"
              class="mb-6"
            />

            <VBtn block color="error" size="large" type="submit" class="mb-6">
              Sign in
            </VBtn>
          </VForm>

          <!-- Footer -->
          <div class="text-center text-body-2 text-medium-emphasis">
            PT. Yamaha Electronic Motor Indonesia
            <div class="mt-1">2024</div>
          </div>
        </VCard>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.login-wrapper {
  position: relative;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
}

.background-container {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 0;
}

.background-image {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  object-fit: cover;
  object-position: center;
}

:deep(.v-img__img) {
  height: 100vh !important;
  width: 100vw !important;
  object-fit: cover !important;
}

:deep(.v-img__content) {
  height: 100vh !important;
  width: 100vw !important;
}

.logo-container {
  z-index: 2;
  position: relative;
  width: 200px;

  a {
    display: block;
    text-decoration: none;
  }
}

.logo-image {
  width: 100%;
  height: auto;

  :deep(.v-img__img) {
    height: auto !important;
    width: 100% !important;
    object-fit: contain !important;
  }

  :deep(.v-img__content),
  :deep(.v-responsive__content) {
    height: auto !important;
    width: 100% !important;
  }
}

.login-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  position: relative;
  z-index: 1;
}

.v-container {
  position: relative;
  z-index: 1;
}
</style>
