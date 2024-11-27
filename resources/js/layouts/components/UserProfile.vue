<script setup>
import { useAbility } from "@casl/vue";
import { useToast } from "vue-toastification";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const router = useRouter();
const toast = useToast();
const ability = useAbility();

const isChangePasswordDialogOpen = ref(false);

// TODO: Get type from backend
const userData = useCookie("userData");

const logout = async () => {
  try {
    const data = await $api("/auth/logout", {
      onResponseError({ response }) {
        toast.error(response._data.message);
      },
    });

    // Remove "accessToken" from cookie
    useCookie("accessToken").value = null;

    // Remove "userData" from cookie
    userData.value = null;

    // Redirect to login page
    await router.push("/login");

    // ℹ️ We had to remove abilities in then block because if we don't nav menu items mutation is visible while redirecting user to login page

    // Remove "userAbilities" from cookie
    useCookie("userAbilityRules").value = null;

    // Reset ability to initial ability
    ability.update([]);
  } catch (err) {
    console.error(err);
  }
};

const userProfileList = [
  { type: "divider" },
  {
    type: "navItem",
    icon: "tabler-user",
    title: "Profile",
    to: {
      name: "apps-user-view-id",
      params: { id: 21 },
    },
  },
  {
    type: "navItem",
    icon: "tabler-settings",
    title: "Settings",
    to: {
      name: "pages-account-settings-tab",
      params: { tab: "account" },
    },
  },
  {
    type: "navItem",
    icon: "tabler-file-dollar",
    title: "Billing Plan",
    to: {
      name: "pages-account-settings-tab",
      params: { tab: "billing-plans" },
    },
    badgeProps: {
      color: "error",
      content: "4",
    },
  },
  { type: "divider" },
  {
    type: "navItem",
    icon: "tabler-currency-dollar",
    title: "Pricing",
    to: { name: "pages-pricing" },
  },
  {
    type: "navItem",
    icon: "tabler-question-mark",
    title: "FAQ",
    to: { name: "pages-faq" },
  },
];
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg v-if="userData && userData.avatar" :src="userData.avatar" />
      <VIcon v-else icon="tabler-user" />

      <!-- SECTION Menu -->
      <VMenu activator="parent" width="240" location="bottom end" offset="12px">
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                  bordered
                >
                  <VAvatar
                    :color="
                      !(userData && userData.avatar) ? 'primary' : undefined
                    "
                    :variant="
                      !(userData && userData.avatar) ? 'tonal' : undefined
                    "
                  >
                    <VImg
                      v-if="userData && userData.avatar"
                      :src="userData.avatar"
                    />
                    <VIcon v-else icon="tabler-user" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-medium">
              {{ userData.name }}
            </VListItemTitle>
            <VListItemTitle>
              <a
                @click="
                  isChangePasswordDialogOpen = !isChangePasswordDialogOpen
                "
                style="cursor: pointer"
                >Change Password
              </a>
            </VListItemTitle>
            <VListItemSubtitle>{{ userData.role }}</VListItemSubtitle>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <div class="px-4 py-2">
              <VBtn
                block
                size="small"
                color="error"
                append-icon="tabler-logout"
                @click="logout"
              >
                Logout
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>

  <ChangePasswordDialog v-model:isDialogVisible="isChangePasswordDialogOpen" />
</template>
