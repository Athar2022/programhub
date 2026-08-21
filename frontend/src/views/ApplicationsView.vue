<script setup>
import { onMounted, ref } from 'vue'
import applicationsService from '../services/applications'

const applications = ref([])
const loading = ref(false)
const error = ref(null)

function formatDate(value) {
  if (!value) {
    return 'غير محدد'
  }

  return new Intl.DateTimeFormat('ar-SA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(value))
}

function statusLabel(status) {
  const labels = {
    draft: 'مسودة',
    submitted: 'مرسل للمراجعة',
    under_review: 'قيد المراجعة',
    approved: 'مقبول',
    rejected: 'مرفوض',
    withdrawn: 'منسحب',
  }

  return labels[status] ?? status ?? 'غير محدد'
}

function statusClass(status) {
  const classes = {
    draft: 'bg-slate-100 text-slate-700',
    submitted: 'bg-amber-100 text-amber-800',
    under_review: 'bg-blue-100 text-blue-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
    withdrawn: 'bg-slate-100 text-slate-600',
  }

  return classes[status] ?? 'bg-slate-100 text-slate-700'
}

async function loadApplications() {
  loading.value = true
  error.value = null

  try {
    const response = await applicationsService.list()
    applications.value = response.data ?? []
  } catch (exception) {
    error.value = exception.response?.data?.message ?? 'تعذر تحميل طلباتك حاليًا.'
  } finally {
    loading.value = false
  }
}

onMounted(loadApplications)
</script>

<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-xl font-bold text-teal-700">ProgramHub</RouterLink>
        <div class="flex items-center gap-4">
          <RouterLink
            to="/dashboard"
            class="text-sm font-semibold text-slate-600 transition hover:text-teal-700"
          >
            لوحة التحكم
          </RouterLink>
          <RouterLink
            to="/programs"
            class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
          >
            استعراض البرامج
          </RouterLink>
        </div>
      </div>
    </header>

    <section class="mx-auto max-w-6xl px-6 py-12">
      <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
          <p class="text-sm font-semibold text-teal-700">مساحة المتقدم</p>
          <h1 class="mt-2 text-3xl font-bold text-slate-950 sm:text-4xl">طلباتي</h1>
          <p class="mt-3 max-w-2xl leading-7 text-slate-600">
            تابع جميع طلبات التقديم التي أنشأتها واطّلع على حالتها والبرنامج المرتبطة به.
          </p>
        </div>

        <button
          type="button"
          :disabled="loading"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-teal-500 hover:text-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
          @click="loadApplications"
        >
          <i
            class="fa-solid"
            :class="loading ? 'fa-spinner fa-spin' : 'fa-rotate'"
            aria-hidden="true"
          ></i>
          تحديث القائمة
        </button>
      </div>

      <div v-if="loading" class="mt-8 grid gap-5 lg:grid-cols-2" aria-live="polite">
        <div
          v-for="placeholder in 4"
          :key="placeholder"
          class="h-56 animate-pulse rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
        ></div>
      </div>

      <div
        v-else-if="error"
        class="mt-8 rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700"
        role="alert"
      >
        <div class="flex items-center gap-3">
          <i class="fa-solid fa-circle-exclamation text-lg" aria-hidden="true"></i>
          <p class="font-semibold">{{ error }}</p>
        </div>
        <button
          type="button"
          class="mt-4 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800"
          @click="loadApplications"
        >
          إعادة المحاولة
        </button>
      </div>

      <div
        v-else-if="applications.length === 0"
        class="mt-8 rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200"
      >
        <i class="fa-solid fa-folder-open text-4xl text-slate-300" aria-hidden="true"></i>
        <h2 class="mt-4 text-xl font-bold text-slate-900">لا توجد طلبات تقديم بعد</h2>
        <p class="mx-auto mt-2 max-w-md leading-7 text-slate-500">
          استعرض البرامج المنشورة وابدأ طلب تقديم جديدًا على البرنامج المناسب لك.
        </p>
        <RouterLink
          to="/programs"
          class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800"
        >
          استعراض البرامج
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        </RouterLink>
      </div>

      <div v-else class="mt-8 grid gap-5 lg:grid-cols-2">
        <article
          v-for="application in applications"
          :key="application.id"
          class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700"
              >
                <i class="fa-solid fa-file-signature text-lg" aria-hidden="true"></i>
              </div>
              <div>
                <p class="text-xs font-semibold text-slate-500">طلب رقم #{{ application.id }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-950">
                  {{ application.program?.title || 'برنامج غير محدد' }}
                </h2>
              </div>
            </div>

            <span
              class="shrink-0 rounded-full px-3 py-1 text-xs font-bold"
              :class="statusClass(application.status)"
            >
              {{ statusLabel(application.status) }}
            </span>
          </div>

          <div class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm text-slate-600">
            <p class="flex items-center gap-3">
              <i class="fa-solid fa-building w-4 text-center text-teal-600" aria-hidden="true"></i>
              <span>{{ application.program?.organization?.name || 'جهة غير محددة' }}</span>
            </p>
            <p class="flex items-center gap-3">
              <i
                class="fa-solid fa-calendar-days w-4 text-center text-teal-600"
                aria-hidden="true"
              ></i>
              <span>تاريخ الإنشاء: {{ formatDate(application.created_at) }}</span>
            </p>
            <p v-if="application.submitted_at" class="flex items-center gap-3">
              <i
                class="fa-solid fa-paper-plane w-4 text-center text-teal-600"
                aria-hidden="true"
              ></i>
              <span>تاريخ الإرسال: {{ formatDate(application.submitted_at) }}</span>
            </p>
          </div>

          <div class="mt-6 flex flex-wrap gap-4 border-t border-slate-100 pt-5">
            <RouterLink
              :to="{ name: 'application-details', params: { application: application.id } }"
              class="inline-flex items-center gap-2 text-sm font-semibold text-teal-700 transition hover:text-teal-900"
            >
              عرض تفاصيل الطلب
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </RouterLink>

            <RouterLink
              v-if="application.program?.id"
              :to="{ name: 'program-details', params: { program: application.program.id } }"
              class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-teal-700"
            >
              عرض البرنامج
              <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
            </RouterLink>
          </div>
        </article>
      </div>
    </section>
  </main>
</template>
