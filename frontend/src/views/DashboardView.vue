<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import notificationsService from '../services/notifications'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)
const showRegistrationSuccess = ref(route.query.registered === '1')
const notifications = ref([])
const unreadCount = ref(0)
const notificationsLoading = ref(false)
const notificationsError = ref(null)
const markingNotificationId = ref(null)
const markingAllAsRead = ref(false)

const canManageOrganizationPrograms = computed(() => {
  const role = user.value?.role

  if (role === 'organization' || role === 'platform_admin') {
    return true
  }

  return (
    user.value?.organizations?.some((organization) =>
      ['owner', 'admin'].includes(organization.pivot?.role),
    ) ?? false
  )
})

const canReviewOrganizationApplications = computed(() => {
  if (user.value?.role === 'platform_admin') {
    return true
  }

  return (
    user.value?.organizations?.some((organization) =>
      ['owner', 'admin', 'reviewer'].includes(organization.pivot?.role),
    ) ?? false
  )
})

function getNotificationData(notification) {
  if (typeof notification.data === 'string') {
    try {
      return JSON.parse(notification.data)
    } catch {
      return {}
    }
  }

  return notification.data ?? {}
}

function notificationTitle(notification) {
  const data = getNotificationData(notification)

  return data.title ?? notification.title ?? 'إشعار جديد'
}

function notificationMessage(notification) {
  const data = getNotificationData(notification)

  return data.message ?? data.body ?? notification.message ?? 'لديك إشعار جديد في ProgramHub.'
}

function formatNotificationDate(value) {
  if (!value) {
    return 'تاريخ غير محدد'
  }

  return new Intl.DateTimeFormat('ar-SA', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function normalizeNotifications(response) {
  const payload = response.notifications ?? response.data ?? []

  if (Array.isArray(payload)) {
    return payload
  }

  return payload.data ?? []
}

async function loadNotifications() {
  notificationsLoading.value = true
  notificationsError.value = null

  try {
    const response = await notificationsService.list()
    notifications.value = normalizeNotifications(response)
    unreadCount.value =
      response.unread_count ?? notifications.value.filter((item) => !item.read_at).length
  } catch (exception) {
    notificationsError.value = exception.response?.data?.message ?? 'تعذر تحميل الإشعارات حاليًا.'
  } finally {
    notificationsLoading.value = false
  }
}

async function markNotificationAsRead(notification) {
  if (notification.read_at) {
    return
  }

  markingNotificationId.value = notification.id
  notificationsError.value = null

  try {
    const response = await notificationsService.markAsRead(notification.id)

    if (response.notification) {
      Object.assign(notification, response.notification)
    } else {
      notification.read_at = new Date().toISOString()
    }

    unreadCount.value = notifications.value.filter((item) => !item.read_at).length
  } catch (exception) {
    notificationsError.value =
      exception.response?.data?.message ?? 'تعذر تعليم الإشعار كمقروء حاليًا.'
  } finally {
    markingNotificationId.value = null
  }
}

async function markAllNotificationsAsRead() {
  if (!unreadCount.value) {
    return
  }

  markingAllAsRead.value = true
  notificationsError.value = null

  try {
    await notificationsService.markAllAsRead()
    const readAt = new Date().toISOString()

    notifications.value.forEach((notification) => {
      notification.read_at = notification.read_at ?? readAt
    })
    unreadCount.value = 0
  } catch (exception) {
    notificationsError.value =
      exception.response?.data?.message ?? 'تعذر تعليم جميع الإشعارات كمقروءة حاليًا.'
  } finally {
    markingAllAsRead.value = false
  }
}

onMounted(async () => {
  if (showRegistrationSuccess.value) {
    window.setTimeout(() => {
      showRegistrationSuccess.value = false
    }, 4000)

    await router.replace({ name: 'dashboard' })
  }

  await loadNotifications()
})

async function handleLogout() {
  await authStore.logout()
  await router.push('/login')
}
</script>

<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-[-12px] opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-300 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-[-12px] opacity-0"
    >
      <div
        v-if="showRegistrationSuccess"
        class="fixed right-4 top-4 z-50 flex w-[calc(100%-2rem)] max-w-sm items-center gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-emerald-800 shadow-lg ring-1 ring-emerald-100 sm:right-6 sm:top-6"
        role="alert"
        aria-live="polite"
      >
        <i class="fa-solid fa-circle-check text-lg text-emerald-600" aria-hidden="true"></i>
        <span class="flex-1 font-semibold">تم إنشاء الحساب بنجاح، مرحبًا بك في ProgramHub.</span>
        <button
          type="button"
          class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
          aria-label="إغلاق الإشعار"
          title="إغلاق الإشعار"
          @click="showRegistrationSuccess = false"
        >
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
    </Transition>

    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/dashboard" class="text-xl font-bold text-teal-700">
          ProgramHub
        </RouterLink>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-400 hover:text-red-600"
          @click="handleLogout"
        >
          تسجيل الخروج
        </button>
      </div>
    </header>

    <section class="mx-auto max-w-6xl px-6 py-12">
      <div
        v-if="showRegistrationSuccess"
        class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
        role="status"
      >
        <i class="fa-solid fa-circle-check text-lg" aria-hidden="true"></i>
        <span class="font-semibold">تم إنشاء الحساب بنجاح، مرحبًا بك في ProgramHub.</span>
      </div>

      <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm font-semibold text-teal-700">مساحة العمل</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-950">مرحبًا {{ user?.name }}</h1>
        <p class="mt-3 text-slate-600">
          تم تسجيل دخولك بنجاح. سنضيف هنا إدارة البرامج والطلبات والإشعارات تدريجيًا.
        </p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
          <RouterLink
            to="/applications"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800"
          >
            <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
            عرض طلباتي
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </RouterLink>

          <RouterLink
            v-if="canManageOrganizationPrograms"
            to="/organization/programs"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-5 py-3 text-sm font-semibold text-teal-800 transition hover:border-teal-300 hover:bg-teal-100"
          >
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
            إدارة برامج الجهة
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </RouterLink>
          <RouterLink
            v-if="canReviewOrganizationApplications"
            to="/organization/applications"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-semibold text-indigo-800 transition hover:border-indigo-300 hover:bg-indigo-100"
          >
            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
            مراجعة طلبات المتقدمين
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </RouterLink>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
          <div class="rounded-xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">البريد الإلكتروني</p>
            <p class="mt-2 font-semibold text-slate-900">{{ user?.email }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">الدور</p>
            <p class="mt-2 font-semibold text-slate-900">{{ user?.role }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">حالة الحساب</p>
            <p class="mt-2 font-semibold text-emerald-700">{{ user?.status }}</p>
          </div>
        </div>
      </div>

      <section class="mt-6 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="flex items-center gap-2 text-sm font-semibold text-teal-700">
              <i class="fa-solid fa-bell" aria-hidden="true"></i>
              الإشعارات
            </p>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">آخر التنبيهات</h2>
            <p class="mt-2 text-sm text-slate-600">لديك {{ unreadCount }} إشعار غير مقروء.</p>
          </div>

          <button
            v-if="unreadCount > 0"
            type="button"
            :disabled="markingAllAsRead"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-teal-200 px-4 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50 disabled:cursor-not-allowed disabled:opacity-60"
            @click="markAllNotificationsAsRead"
          >
            <i
              class="fa-solid"
              :class="markingAllAsRead ? 'fa-spinner fa-spin' : 'fa-check-double'"
              aria-hidden="true"
            ></i>
            {{ markingAllAsRead ? 'جارٍ التحديث...' : 'تعليم الكل كمقروء' }}
          </button>
        </div>

        <div
          v-if="notificationsError"
          class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
          role="alert"
        >
          <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
          <p class="font-semibold">{{ notificationsError }}</p>
        </div>

        <div v-if="notificationsLoading" class="mt-6 space-y-3">
          <div class="h-20 animate-pulse rounded-xl bg-slate-100"></div>
          <div class="h-20 animate-pulse rounded-xl bg-slate-100"></div>
        </div>

        <div
          v-else-if="notifications.length === 0"
          class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 ring-1 ring-slate-200"
        >
          <i class="fa-regular fa-bell-slash mb-3 text-2xl text-slate-300" aria-hidden="true"></i>
          <p>لا توجد إشعارات حاليًا.</p>
        </div>

        <div v-else class="mt-6 space-y-3">
          <article
            v-for="notification in notifications"
            :key="notification.id"
            class="rounded-xl border p-4 transition"
            :class="
              notification.read_at ? 'border-slate-200 bg-white' : 'border-teal-100 bg-teal-50/60'
            "
          >
            <div class="flex items-start gap-3">
              <span
                class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                :class="
                  notification.read_at ? 'bg-slate-100 text-slate-500' : 'bg-teal-100 text-teal-700'
                "
              >
                <i
                  class="fa-solid"
                  :class="notification.read_at ? 'fa-envelope-open' : 'fa-envelope'"
                  aria-hidden="true"
                ></i>
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                  <h3 class="font-bold text-slate-950">{{ notificationTitle(notification) }}</h3>
                  <time class="text-xs text-slate-500">
                    {{ formatNotificationDate(notification.created_at) }}
                  </time>
                </div>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                  {{ notificationMessage(notification) }}
                </p>

                <button
                  v-if="!notification.read_at"
                  type="button"
                  :disabled="markingNotificationId === notification.id"
                  class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-teal-700 transition hover:text-teal-900 disabled:cursor-not-allowed disabled:opacity-60"
                  @click="markNotificationAsRead(notification)"
                >
                  <i
                    class="fa-solid"
                    :class="
                      markingNotificationId === notification.id ? 'fa-spinner fa-spin' : 'fa-check'
                    "
                    aria-hidden="true"
                  ></i>
                  {{
                    markingNotificationId === notification.id ? 'جارٍ التحديث...' : 'تعليم كمقروء'
                  }}
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>
    </section>
  </main>
</template>
