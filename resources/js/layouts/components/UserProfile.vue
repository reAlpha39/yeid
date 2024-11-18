<script setup>
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const router = useRouter();
const toast = useToast();
const auth = useAuthStore();

// TODO: Get type from backend
const userData = computed(() => auth.userData);

const logout = async () => {
  try {
    const { success, error } = await auth.logout();

    if (!success) {
      toast.error(error.message);
      return;
    }

    await router.push("/login");
  } catch (error) {
    console.error("Logout error:", error);
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
</template>
