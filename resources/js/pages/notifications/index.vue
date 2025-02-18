<script setup>
import moment from "moment";
import "moment/locale/id";
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
moment.locale("id");

const router = useRouter();
const notifications = ref([]);
const loading = ref(false);
const unreadCount = ref(0);
const hasMoreItems = ref(true);
const containerRef = ref(null);
const page = ref(1);
const itemsPerPage = ref(10);

// Create scroll handler
const handleScroll = async () => {
  if (!containerRef.value || loading.value || !hasMoreItems.value) return;

  const container = containerRef.value;
  const scrollPosition = container.scrollTop + container.clientHeight;
  const scrollHeight = container.scrollHeight;

  // Load more when user scrolls to the bottom (with a small threshold)
  if (scrollHeight - scrollPosition < 100) {
    await fetchNotifications(true);
  }
};

// Get URL for a notification
const getNotificationUrl = (notification) => {
  const route = {
    path: "/maintenance-database-system/department-request/detail",
    query: {
      record_id: notification.sourceId,
      to_approve: "1",
    },
  };
  return router.resolve(route).href;
};

// Fetch notifications
const fetchNotifications = async (append = false) => {
  if (loading.value) return;

  try {
    loading.value = true;

    // If not appending, reset to page 1
    if (!append) {
      page.value = 1;
    }

    const response = await $api("/inbox", {
      params: {
        page: page.value,
        per_page: itemsPerPage.value,
      },
    });

    const formattedNotifications = response.data.map((notification) => ({
      id: notification.id,
      title: notification.title,
      subtitle: notification.message,
      time: moment(notification.created_at).format(
        "dddd, D MMMM YYYY HH:mm:ss"
      ),
      isSeen: notification.status === "read",
      category: notification.category,
      sourceId: notification.source_id,
    }));

    // Either replace or append notifications based on the append parameter
    if (append) {
      notifications.value = [...notifications.value, ...formattedNotifications];
      page.value++; // Increment page only when appending
    } else {
      notifications.value = formattedNotifications;
    }

    // Update whether there are more items to load
    hasMoreItems.value = formattedNotifications.length === itemsPerPage.value;
  } catch (error) {
    console.error("Error fetching notifications:", error);
  } finally {
    loading.value = false;
  }
};

const fetchUnreadCount = async () => {
  try {
    const response = await $api("/inbox/unread-count");
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error("Error fetching unread count:", error);
  }
};

const markAsRead = async (id) => {
  try {
    await $api(`/inbox/${id}/read`, {
      method: "PATCH",
    });
    await fetchNotifications(); // Reset list to page 1
    await fetchUnreadCount();
  } catch (error) {
    console.error("Error marking notification as read:", error);
  }
};

const markAllAsRead = async () => {
  try {
    const unreadIds = notifications.value
      .filter((n) => !n.isSeen)
      .map((n) => n.id);

    if (unreadIds.length) {
      await $api("/inbox/batch/read", {
        method: "POST",
        body: { ids: unreadIds },
      });
      await fetchNotifications(); // Reset list to page 1
      await fetchUnreadCount();
    }
  } catch (error) {
    console.error("Error marking all as read:", error);
  }
};

const deleteNotification = async (id) => {
  try {
    await $api(`/inbox/${id}/delete`, { method: "DELETE" });
    notifications.value = notifications.value.filter((n) => n.id !== id);
    await fetchUnreadCount();
  } catch (error) {
    console.error("Error deleting notification:", error);
  }
};

const handleNotificationClick = async (notification) => {
  if (!notification.isSeen) {
    await markAsRead(notification.id);
  }

  if (["approval", "rejection", "revision"].includes(notification.category)) {
    await openDetailPage(notification.sourceId);
  }
};

async function openDetailPage(id) {
  await router.push({
    path: "/maintenance-database-system/department-request/detail",
    query: { record_id: id, to_approve: "1" },
  });
}

onMounted(async () => {
  await fetchNotifications();
  await fetchUnreadCount();
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[{ title: 'Notifications', class: 'text-h4' }]"
    />
  </div>

  <VCard class="mb-6">
    <VCardText>
      <div class="d-flex align-center mb-4">
        <VSpacer />
        <VBtn
          v-if="notifications.length && unreadCount > 0"
          variant="text"
          @click="markAllAsRead"
        >
          Mark all as read
        </VBtn>
      </div>

      <VDivider class="mb-4" />

      <div
        ref="containerRef"
        class="notification-table-wrapper"
        @scroll="handleScroll"
      >
        <VList class="notification-list rounded-0 py-0">
          <template
            v-for="(notification, index) in notifications"
            :key="notification.id"
          >
            <VDivider v-if="index > 0" />
            <VListItem
              tag="a"
              :href="getNotificationUrl(notification)"
              lines="one"
              min-height="66px"
              class="list-item-hover-class"
              @click.prevent="handleNotificationClick(notification)"
            >
              <div class="d-flex align-start gap-3">
                <VIcon
                  size="10"
                  icon="tabler-circle-filled"
                  :color="notification.isSeen ? 'transparent' : 'primary'"
                  class="mb-2"
                />

                <div>
                  <p class="text-sm font-weight-medium mb-1">
                    {{ notification.title }}
                  </p>
                  <p
                    class="text-body-2 mb-2"
                    style="letter-spacing: 0.4px !important; line-height: 18px"
                  >
                    {{ notification.subtitle }}
                  </p>
                  <p
                    class="text-sm text-disabled mb-0"
                    style="letter-spacing: 0.4px !important; line-height: 18px"
                  >
                    {{ notification.time }}
                  </p>
                </div>
                <VSpacer />

                <div class="d-flex flex-column align-end">
                  <VIcon
                    size="20"
                    icon="tabler-trash"
                    class="visible-in-hover"
                    @click.stop="deleteNotification(notification.id)"
                  />
                </div>
              </div>
            </VListItem>
          </template>

          <VListItem
            v-if="!loading && !notifications.length"
            class="text-center text-medium-emphasis"
          >
            <VListItemTitle>No Notifications Found!</VListItemTitle>
          </VListItem>
        </VList>

        <!-- Loading indicator for infinite scroll -->
        <div v-if="loading" class="d-flex justify-center pa-4">
          <VProgressCircular indeterminate size="32" />
        </div>
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.notification-table-wrapper {
  position: relative;
  height: 562px;
  overflow-y: auto;
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

.max-width-200 {
  max-width: 200px;
}

.max-width-300 {
  max-width: 300px;
}
</style>
