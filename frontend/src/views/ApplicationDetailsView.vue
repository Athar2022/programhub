<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import applicationsService from '../services/applications'

const route = useRoute()
const router = useRouter()

const application = ref(null)
const loading = ref(false)
const error = ref(null)
const saving = ref(false)
const submitting = ref(false)
const actionError = ref(null)
const successMessage = ref(null)
const form = reactive({
  notes: '',
})

let successTimeoutId = null

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

function showSuccess(message) {
  successMessage.value = message

  if (successTimeoutId) {
    window.clearTimeout(successTimeoutId)
  }

  successTimeoutId = window.setTimeout(() => {
    successMessage.value = null
  }, 4000)
}

async function loadApplication() {
  loading.value = true
  error.value = null

  try {
    const response = await applicationsService.show(route.params.application)
    application.value = response.application ?? null
    form.notes = application.value?.notes ?? ''
  } catch (exception) {
    error.value = exception.response?.data?.message ?? 'تعذر تحميل تفاصيل الطلب حاليًا.'
  } finally {
    loading.value = false
  }
}

async function saveApplication() {
  if (!application.value || application.value.status !== 'draft') {
    return
  }

  saving.value = true
  actionError.value = null

  try {
    const response = await applicationsService.update(application.value.id, {
      notes: form.notes.trim() || null,
    })

    application.value = response.application ?? application.value
    form.notes = application.value.notes ?? ''
    showSuccess('تم حفظ تعديلات الطلب بنجاح.')
  } catch (exception) {
    actionError.value =
      exception.response?.data?.errors?.notes?.[0] ??
      exception.response?.data?.message ??
      'تعذر حفظ تعديلات الطلب حاليًا.'
  } finally {
    saving.value = false
  }
}

async function submitApplication() {
  if (!application.value || application.value.status !== 'draft') {
    return
  }

  submitting.value = true
  actionError.value = null

  try {
    const response = await applicationsService.submit(application.value.id)
    application.value = response.application ?? application.value
    showSuccess('تم إرسال الطلب للمراجعة بنجاح.')
  } catch (exception) {
    actionError.value = exception.response?.data?.message ?? 'تعذر إرسال الطلب للمراجعة حاليًا.'
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'applications' })
}

onMounted(loadApplication)

onBeforeUnmount(() => {
  if (successTimeoutId) {
    window.clearTimeout(successTimeoutId)
  }
})
</script>

<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <Transition name="toast">
      <div
        v-if="successMessage"
        class="fixed left-1/2 top-5 z-50 flex w-[calc(100%-2rem)] max-w-md -translate-x-1/2 items-center gap-3 rounded-xl bg-emerald-600 px-5 py-4 text-white shadow-lg"
        role="status"
        aria-live="polite"
      >
        <i class="fa-solid fa-circle-check text-xl" aria-hidden="true"></i>
        <p class="text-sm font-semibold">{{ successMessage }}</p>
      </div>
    </Transition>

    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-xl font-bold text-teal-700">ProgramHub</RouterLink>
        <div class="flex items-center gap-4">
          <RouterLink to="/applications" class="text-sm font-semibold text-slate-600 transition hover:text-teal-700">
            طلباتي
          </RouterLink>
          <RouterLink
            to="/dashboard"
            class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
          >
            لوحة التحكم
          </RouterLink>
        </div>
      </div>
    </header>

    <section class="mx-auto max-w-5xl px-6 py-12">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-sm font-semibold text-teal-700 transition hover:text-teal-900"
        @click="goBack"
      >
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        العودة إلى طلباتي
      </button>

      <div
        v-if="loading"
        class="mt-8 h-[34rem] animate-pulse rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
      ></div>

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
          @click="loadApplication"
        >
          إعادة المحاولة
        </button>
      </div>

      <div
        v-else-if="!application"
        class="mt-8 rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200"
      >
        <i class="fa-solid fa-file-circle-question text-4xl text-slate-300" aria-hidden="true"></i>
        <h1 class="mt-4 text-xl font-bold text-slate-900">الطلب غير موجود</h1>
        <p class="mt-2 text-slate-500">قد يكون الطلب غير متاح أو لا تملك صلاحية الوصول إليه.</p>
      </div>

      <article v-else class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="bg-gradient-to-l from-teal-700 to-teal-600 px-6 py-10 text-white sm:px-10">
          <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
              <p class="text-sm font-semibold text-teal-100">تفاصيل طلب التقديم</p>
              <h1 class="mt-3 text-3xl font-bold sm:text-4xl">طلب رقم #{{ application.id }}</h1>
              <p class="mt-3 flex items-center gap-2 text-teal-50">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                تاريخ الإنشاء: {{ formatDate(application.created_at) }}
              </p>
            </div>
            <span class="rounded-full bg-white px-4 py-2 text-sm font-bold" :class="statusClass(application.status)">
              {{ statusLabel(application.status) }}
            </span>
          </div>
        </div>

        <div class="p-6 sm:p-10">
          <section class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-semibold text-teal-700">البرنامج</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-950">
                  {{ application.program?.title || 'برنامج غير محدد' }}
                </h2>
              </div>
              <i class="fa-solid fa-graduation-cap text-2xl text-teal-600" aria-hidden="true"></i>
            </div>

            <div class="mt-5 grid gap-4 text-sm text-slate-600 sm:grid-cols-2">
              <p class="flex items-center gap-3">
                <i class="fa-solid fa-building w-4 text-center text-teal-600" aria-hidden="true"></i>
                <span>{{ application.program?.organization?.name || 'جهة غير محددة' }}</span>
              </p>
              <p class="flex items-center gap-3">
                <i class="fa-solid fa-location-dot w-4 text-center text-teal-600" aria-hidden="true"></i>
                <span>{{ application.program?.location || 'الموقع غير محدد' }}</span>
              </p>
            </div>

            <RouterLink
              v-if="application.program?.id"
              :to="{ name: 'program-details', params: { program: application.program.id } }"
              class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-teal-700 transition hover:text-teal-900"
            >
              عرض تفاصيل البرنامج
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </RouterLink>
          </section>

          <section class="mt-8">
            <div class="flex items-center justify-between gap-4">
              <h2 class="flex items-center gap-3 text-xl font-bold text-slate-950">
                <i class="fa-solid fa-note-sticky text-teal-600" aria-hidden="true"></i>
                ملاحظات الطلب
              </h2>
              <span v-if="application.status === 'draft'" dir="ltr" class="text-xs text-slate-500">
                {{ form.notes.length }} / 5000
              </span>
            </div>

            <textarea
              v-if="application.status === 'draft'"
              v-model="form.notes"
              rows="7"
              maxlength="5000"
              class="mt-4 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
              placeholder="اكتب ملاحظاتك الإضافية هنا..."
            ></textarea>

            <p v-else class="mt-4 whitespace-pre-line rounded-xl bg-slate-50 p-5 leading-7 text-slate-600 ring-1 ring-slate-200">
              {{ application.notes || 'لا توجد ملاحظات مضافة لهذا الطلب.' }}
            </p>
          </section>

          <div
            v-if="actionError"
            class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
            role="alert"
          >
            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <p class="font-semibold">{{ actionError }}</p>
          </div>

          <div v-if="application.status === 'draft'" class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <button
              type="button"
              :disabled="saving || submitting"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-teal-700 px-5 py-3 text-sm font-semibold text-teal-700 transition hover:bg-teal-50 disabled:cursor-not-allowed disabled:opacity-60"
              @click="saveApplication"
            >
              <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'" aria-hidden="true"></i>
              {{ saving ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
            </button>
            <button
              type="button"
              :disabled="saving || submitting"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60"
              @click="submitApplication"
            >
              <i class="fa-solid" :class="submitting ? 'fa-spinner fa-spin' : 'fa-paper-plane'" aria-hidden="true"></i>
              {{ submitting ? 'جارٍ إرسال الطلب...' : 'إرسال للمراجعة' }}
            </button>
          </div>

          <div v-else class="mt-8 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
            <i class="fa-solid fa-circle-check ml-2" aria-hidden="true"></i>
            تم إرسال هذا الطلب، ولا يمكن تعديله حاليًا.
          </div>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
}
</style>
