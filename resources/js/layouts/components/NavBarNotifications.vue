<script setup>
import moment from "moment";
import "moment/locale/id";
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
moment.locale("id");

const props = defineProps({
  badgeProps: {
    type: Object,
    required: false,
    default: undefined,
  },
  location: {
    type: String,
    required: false,
    default: "bottom end",
  },
});

const router = useRouter();
const menuOpen = ref(false);

const notifications = ref([]);
const loading = ref(false);
const unreadCount = ref(0);
const perPage = 10;

// get notification URL
const getNotificationUrl = (notification) => {
  const baseUrl = window.location.origin;
  let path = "";

  if (!notification) {
    return `${baseUrl}/notifications`;
  }

  const query = `record_id=${notification.sourceId}`;

  if (notification.category === "approval") {
    path = "/maintenance-database-system/department-request/detail";
    return `${baseUrl}${path}?${query}&to_approve=1`;
  } else if (notification.category === "rejection") {
    path = "/maintenance-database-system/department-request/detail";
    return `${baseUrl}${path}?${query}`;
  } else if (notification.category === "revision") {
    path = "/maintenance-database-system/department-request/add";
    return `${baseUrl}${path}?${query}`;
  }

  return `${baseUrl}/notifications`;
};

// Fetch notifications
const fetchNotifications = async () => {
  try {
    loading.value = true;
    const response = await $api("/inbox", {
      params: {
        per_page: perPage,
      },
    });

    notifications.value = response.data.map((notification) => ({
      id: notification.id,
      title: notification.title,
      subtitle: notification.message,
      time: moment(notification.created_at).format(
        "dddd, D MMMM YYYY HH:mm:ss"
      ),
      isSeen: notification.status === "read",
      category: notification.category,
      sourceType: notification.source_type,
      sourceId: notification.source_id,
    }));
  } catch (error) {
    console.error("Error fetching notifications:", error);
  } finally {
    loading.value = false;
  }
};

// Fetch unread count
const fetchUnreadCount = async () => {
  try {
    const response = await $api("/inbox/unread-count");
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error("Error fetching unread count:", error);
  }
};

// Computed properties
const isAllRead = computed(() => unreadCount.value === 0);

// Methods for handling notifications
const markAsRead = async (id) => {
  try {
    await $api(`/inbox/${id}/read`, {
      method: "PATCH",
    });
    await fetchNotifications();
    await fetchUnreadCount();
  } catch (error) {
    console.error("Error marking notification as read:", error);
  }
};

const markAllAsRead = async () => {
  try {
    await $api("/inbox/batch/read", {
      method: "POST",
    });
    await fetchNotifications();
    await fetchUnreadCount();
  } catch (error) {
    console.error("Error marking all as read:", error);
  }
};

const deleteNotification = async (id) => {
  try {
    await $api(`/inbox/${id}/delete`, { method: "DELETE" });
    await fetchNotifications();
    await fetchUnreadCount();
  } catch (error) {
    console.error("Error deleting notification:", error);
  }
};

const handleNotificationClick = async (notification) => {
  if (!notification.isSeen) {
    await markAsRead(notification.id);
  }

  menuOpen.value = false;
  const currentRoute = router.currentRoute.value;
  const newQuery = { record_id: notification.sourceId };

  try {
    if (notification.category === "approval") {
      newQuery.to_approve = "1";
      if (
        currentRoute.path ===
        "/maintenance-database-system/department-request/detail"
      ) {
        // Force reload by replacing current route
        await router.replace({
          path: "/maintenance-database-system/department-request/detail",
          query: { ...newQuery, _reload: Date.now() },
        });
        window.location.reload();
      } else {
        await router.push({
          path: "/maintenance-database-system/department-request/detail",
          query: newQuery,
        });
      }
    } else if (notification.category === "rejection") {
      if (
        currentRoute.path ===
        "/maintenance-database-system/department-request/detail"
      ) {
        await router.replace({
          path: "/maintenance-database-system/department-request/detail",
          query: { ...newQuery, _reload: Date.now() },
        });
        window.location.reload();
      } else {
        await router.push({
          path: "/maintenance-database-system/department-request/detail",
          query: newQuery,
        });
      }
    } else if (notification.category === "revision") {
      if (
        currentRoute.path ===
        "/maintenance-database-system/department-request/add"
      ) {
        await router.replace({
          path: "/maintenance-database-system/department-request/add",
          query: { ...newQuery, _reload: Date.now() },
        });
        window.location.reload();
      } else {
        await router.push({
          path: "/maintenance-database-system/department-request/add",
          query: newQuery,
        });
      }
    }
  } catch (error) {
    console.error("Navigation error:", error);
  }
};

const handleViewAllNotificationClick = async () => {
  menuOpen.value = false;
  await router.push({
    path: "/notifications",
  });
};

// Initialize component
onMounted(async () => {
  await fetchNotifications();
  await fetchUnreadCount();
});
</script>

<template>
  <IconBtn id="notification-btn">
    <VBadge
      v-bind="props.badgeProps"
      :model-value="unreadCount > 0"
      color="error"
      :content="unreadCount"
      max="99"
      offset-x="2"
      offset-y="3"
    >
      <VIcon size="24" icon="tabler-bell" />
    </VBadge>

    <VMenu
      v-model="menuOpen"
      activator="parent"
      width="380px"
      :location="props.location"
      offset="12px"
      :close-on-content-click="false"
    >
      <VCard class="d-flex flex-column">
        <!-- Header -->
        <VCardItem class="notification-section">
          <VCardTitle class="text-h6"> Notifications </VCardTitle>

          <template #append>
            <VChip
              v-if="unreadCount > 0"
              size="small"
              color="primary"
              class="me-2"
            >
              {{ unreadCount }} New
            </VChip>
            <IconBtn
              v-if="notifications.length"
              size="34"
              @click="markAllAsRead"
            >
              <VIcon
                size="20"
                color="high-emphasis"
                :icon="isAllRead ? 'tabler-mail-opened' : 'tabler-mail'"
              />

              <VTooltip activator="parent" location="start">
                Mark all as read
              </VTooltip>
            </IconBtn>
          </template>
        </VCardItem>

        <VDivider />

        <!-- Notifications list -->
        <VProgressLinear v-if="loading" indeterminate />

        <PerfectScrollbar
          :options="{ wheelPropagation: false }"
          style="max-block-size: 23.75rem"
        >
          <VList class="notification-list rounded-0 py-0">
            <template
              v-for="(notification, index) in notifications"
              :key="notification.id"
            >
              <VDivider v-if="index > 0" />
              <VListItem
                link
                lines="one"
                min-height="66px"
                class="list-item-hover-class"
                @click="handleNotificationClick(notification)"
                :href="getNotificationUrl(notification)"
              >
                <div class="d-flex align-start gap-3">
                  <VIcon
                    v-if="!notification.isSeen"
                    size="10"
                    icon="tabler-circle-filled"
                    color="primary"
                    class="mb-2"
                  />

                  <div v-else class="mx-1"></div>

                  <div>
                    <p class="text-sm font-weight-medium mb-1">
                      {{ notification.title }}
                    </p>
                    <p
                      class="text-body-2 mb-2"
                      style="
                        letter-spacing: 0.4px !important;
                        line-height: 18px;
                      "
                    >
                      {{ notification.subtitle }}
                    </p>
                    <p
                      class="text-sm text-disabled mb-0"
                      style="
                        letter-spacing: 0.4px !important;
                        line-height: 18px;
                      "
                    >
                      {{ notification.time }}
                    </p>
                  </div>
                  <VSpacer />

                  <!-- <div class="d-flex flex-column align-end">
                    <VIcon
                      size="20"
                      icon="tabler-trash"
                      class="visible-in-hover"
                      @click.stop="deleteNotification(notification.id)"
                    />
                  </div> -->
                </div>
              </VListItem>
            </template>

            <VListItem
              v-if="!loading && !notifications.length"
              class="text-center text-medium-emphasis"
              style="block-size: 56px"
            >
              <VListItemTitle>No Notifications Found!</VListItemTitle>
            </VListItem>
          </VList>
        </PerfectScrollbar>

        <VDivider />

        <!-- Footer -->
        <VCardText v-if="notifications.length" class="pa-4">
          <VBtn
            block
            size="small"
            @click="handleViewAllNotificationClick"
            :href="getNotificationUrl()"
          >
            View All Notifications
          </VBtn>
        </VCardText>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style lang="scss">
.notification-section {
  padding-block: 0.75rem;
  padding-inline: 1rem;
}

.list-item-hover-class {
  .visible-in-hover {
    display: none;
  }

  &:hover {
    .visible-in-hover {
      display: block;
    }
  }
}

.notification-list.v-list {
  .v-list-item {
    border-radius: 0 !important;
    margin: 0 !important;
    padding-block: 0.75rem !important;
  }
}
</style>
