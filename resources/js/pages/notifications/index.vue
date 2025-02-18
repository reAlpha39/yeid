<script setup>
import moment from "moment";
import "moment/locale/id";
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
moment.locale("id");

const router = useRouter();
const notifications = ref([]);
const loading = ref(false);
const unreadCount = ref(0);

// Pagination
const page = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);

// Fetch notifications with pagination and sorting
const fetchNotifications = async () => {
  try {
    loading.value = true;
    const response = await $api("/inbox", {
      params: {
        page: page.value,
        per_page: itemsPerPage.value,
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
      sourceId: notification.source_id,
    }));

    // Update pagination from response
    const pagination = response.pagination;
    totalItems.value = pagination.total;
    page.value = pagination.current_page;
    itemsPerPage.value = pagination.per_page;
  } catch (error) {
    console.error("Error fetching notifications:", error);
  } finally {
    loading.value = false;
  }
};

// Handle pagination changes
const handlePageChange = async (newPage) => {
  page.value = newPage;
  await fetchNotifications();
};

const handleItemsPerPageChange = async (newItemsPerPage) => {
  itemsPerPage.value = newItemsPerPage;
  page.value = 1;
  await fetchNotifications();
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
    const unreadIds = notifications.value
      .filter((n) => !n.isSeen)
      .map((n) => n.id);

    if (unreadIds.length) {
      await $api("/inbox/batch/read", {
        method: "POST",
        body: { ids: unreadIds },
      });
      await fetchNotifications();
      await fetchUnreadCount();
    }
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

  if (notification.category === "approval") {
    await openDetailPage(notification.sourceId);
  }

  if (notification.category === "rejection") {
    await openDetailPage(notification.sourceId);
  }

  if (notification.category === "revision") {
    await openDetailPage(notification.sourceId);
  }
};

async function openDetailPage(id) {
  await router.push({
    path: "/maintenance-database-system/department-request/detail",
    query: { record_id: id, to_approve: "1" },
  });
}

// Initialize component
onMounted(async () => {
  await fetchNotifications();
  await fetchUnreadCount();
});
</script>

<template>
  <div>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[
        {
          title: 'Notifications',
          class: 'text-h4',
        },
      ]"
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

      <div class="notification-table-wrapper">
        <PerfectScrollbar :options="{ wheelPropagation: false }">
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
        </PerfectScrollbar>
      </div>

      <!-- Loading Overlay -->
      <VOverlay v-model="loading" class="align-center justify-center">
        <VProgressCircular indeterminate size="64" />
      </VOverlay>

      <!-- Pagination -->
      <div class="d-flex align-center justify-space-between mt-4">
        <VSelect
          v-model="itemsPerPage"
          :items="[10, 25, 50, 100]"
          style="max-width: 150px"
          @update:model-value="handleItemsPerPageChange"
        />

        <VPagination
          v-model="page"
          :length="Math.ceil(totalItems / itemsPerPage)"
          :total-visible="5"
          @update:model-value="handlePageChange"
        />
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.notification-table-wrapper {
  position: relative;
  min-height: 400px;
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
