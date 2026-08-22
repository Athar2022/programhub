<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import applicationsService from '../services/applications'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const applications = ref([])
const loading = ref(false)
const error = ref(null)
const toast = ref(null)
const statusFilter = ref('all')
const selectedApplication = ref(null)
const submittingReview = ref(false)
const reviewError = ref(null)

const reviewForm = reactive({
  status: 'under_review',
  notes: '',
})

const currentOrganization = computed(() => user.value?.organizations?.[0] ?? null)
const isPlatformAdmin = computed(() => user.value?.role === 'platform_admin')
const organizationName = computed(() => {
  if (isPlatformAdmin.value) {
    return 'جميع الجهات'
  }

  return currentOrganization.value?.name ?? 'الجهة المنظمة'
})

const filteredApplications = computed(() => {
  if (statusFilter.value === 'all') {
    return applications.value
  }

  return applications.value.filter((application) => application.status === statusFilter.value)
})

const submittedCount = computed(() =>
  applications.value.filter((application) => application.status === 'submitted').length,
)
const underReviewCount = computed(() =>
  applications.value.filter((application) => application.status === 'under_review').length,
)
const acceptedCount = computed(() =>
  applications.value.filter((application) => application.status === 'accepted').length,
)
const rejectedCount = computed(() =>
  applications.value.filter((application) => application.status === 'rejected').length,
)

function showToast(message, type = 'success') {
  toast.value = { message, type }

  window.setTimeout(() => {
    toast.value = null
  }, 4000)
}

function getErrorMessage(exception, fallback) {
  const validationErrors = exception.response?.data?.errors

  if (validationErrors) {
    const firstError = Object.values(validationErrors).flat()[0]

    if (firstError) {
      return firstError
    }
  }

  return exception.response?.data?.message ?? fallback
}

function normalizeApplications(response) {
  const payload = response.data ?? response.applications ?? []

  if (Array.isArray(payload)) {
    return payload
  }

  return payload.data ?? []
}

function formatDate(value, includeTime = false) {
  if (!value) {
    return 'غير محدد'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return 'غير محدد'
  }

  return new Intl.DateTimeFormat('ar-SA', {
    dateStyle: 'medium',
    ...(includeTime ? { timeStyle: 'short' } : {}),
  }).format(date)
}

function statusLabel(status) {
  const labels = {
    draft: 'مسودة',
    submitted: 'مرسل للمراجعة',
    under_review: 'قيد المراجعة',
    accepted: 'مقبول',
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
    accepted: 'bg-emerald-100 text-emerald-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
    withdrawn: 'bg-slate-100 text-slate-600',
  }

  return classes[status] ?? 'bg-slate-100 text-slate-700'
}

function statusIcon(status) {
  const icons = {
    submitted: 'fa-solid fa-paper-plane',
    under_review: 'fa-solid fa-magnifying-glass',
    accepted: 'fa-solid fa-circle-check',
    approved: 'fa-solid fa-circle-check',
    rejected: 'fa-solid fa-circle-xmark',
  }

  return icons[status] ?? 'fa-solid fa-file-lines'
}

function canReview(application) {
  return ['submitted', 'under_review'].includes(application.status)
}

async function loadApplications() {
  loading.value = true
  error.value = null

  try {
    const response = await applicationsService.list({ per_page: 100 })
    applications.value = normalizeApplications(response)
  } catch (exception) {
    error.value = getErrorMessage(exception, 'تعذر تحميل طلبات المتقدمين.')
  } finally {
    loading.value = false
  }
}

function openReview(application) {
  selectedApplication.value = application
  reviewForm.status = application.status === 'submitted' ? 'under_review' : application.status
  reviewForm.notes = application.notes ?? ''
  reviewError.value = null
}

function closeReview() {
  if (submittingReview.value) {
    return
  }

  selectedApplication.value = null
  reviewError.value = null
}

async function submitReview() {
  if (!selectedApplication.value) {
    return
  }

  submittingReview.value = true
  reviewError.value = null

  try {
    const response = await applicationsService.review(selectedApplication.value.id, {
      status: reviewForm.status,
      notes: reviewForm.notes.trim() || null,
    })

    if (response.application) {
      const index = applications.value.findIndex(
        (application) => application.id === selectedApplication.value.id,
      )

      if (index !== -1) {
        applications.value[index] = response.application
      }
    } else {
      await loadApplications()
    }

    showToast('تم حفظ مراجعة الطلب بنجاح.')
    closeReview()
  } catch (exception) {
    reviewError.value = getErrorMessage(exception, 'تعذر حفظ مراجعة الطلب.')
  } finally {
    submittingReview.value = false
  }
}

onMounted(loadApplications)
</script>

<template>
  <div dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <Transition name="toast">
      <div
        v-if="toast"
        class="fixed inset-x-4 top-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-2xl border px-4 py-3 text-sm font-medium shadow-xl sm:inset-x-auto sm:right-6 sm:left-auto"
        :class="toast.type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
        role="status"
      >
        <i
          class="mt-0.5 text-base"
          :class="toast.type === 'error' ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check'"
          aria-hidden="true"
        ></i>
        <span>{{ toast.message }}</span>
      </div>
    </Transition>

    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <RouterLink to="/dashboard" class="text-xl font-bold text-teal-700">ProgramHub</RouterLink>
        <div class="flex items-center gap-3">
          <RouterLink
            to="/organization/programs"
            class="hidden text-sm font-semibold text-slate-600 transition hover:text-teal-700 sm:inline-flex"
          >
            إدارة البرامج
          </RouterLink>
          <RouterLink
            to="/dashboard"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-teal-300 hover:text-teal-700"
          >
            لوحة التحكم
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
      <section class="rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl sm:px-8">
        <button
          type="button"
          class="mb-5 inline-flex items-center gap-2 text-sm text-slate-300 transition hover:text-white"
          @click="router.push('/dashboard')"
        >
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          العودة إلى لوحة التحكم
        </button>
        <p class="text-sm font-semibold text-teal-300">مساحة المراجعة</p>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">طلبات {{ organizationName }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
              راجع طلبات المتقدمين، اطلع على تفاصيلها، وسجّل قرار المراجعة والملاحظات.
            </p>
          </div>
          <div class="hidden h-16 w-16 items-center justify-center rounded-2xl bg-teal-500/15 text-3xl text-teal-300 sm:flex">
            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
          </div>
        </div>
      </section>

      <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="ملخص الطلبات">
        <button type="button" class="rounded-2xl border border-slate-200 bg-white p-5 text-right shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="statusFilter === 'submitted' ? 'ring-2 ring-amber-300' : ''" @click="statusFilter = 'submitted'">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500">مرسلة للمراجعة</span>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></span>
          </div>
          <p class="mt-4 text-3xl font-bold text-slate-950">{{ submittedCount }}</p>
        </button>
        <button type="button" class="rounded-2xl border border-slate-200 bg-white p-5 text-right shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="statusFilter === 'under_review' ? 'ring-2 ring-blue-300' : ''" @click="statusFilter = 'under_review'">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500">قيد المراجعة</span>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
          </div>
          <p class="mt-4 text-3xl font-bold text-slate-950">{{ underReviewCount }}</p>
        </button>
        <button type="button" class="rounded-2xl border border-slate-200 bg-white p-5 text-right shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="statusFilter === 'accepted' ? 'ring-2 ring-emerald-300' : ''" @click="statusFilter = 'accepted'">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500">مقبولة</span>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
          </div>
          <p class="mt-4 text-3xl font-bold text-slate-950">{{ acceptedCount }}</p>
        </button>
        <button type="button" class="rounded-2xl border border-slate-200 bg-white p-5 text-right shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="statusFilter === 'rejected' ? 'ring-2 ring-red-300' : ''" @click="statusFilter = 'rejected'">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500">مرفوضة</span>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-700"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>
          </div>
          <p class="mt-4 text-3xl font-bold text-slate-950">{{ rejectedCount }}</p>
        </button>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-semibold text-teal-700">سجل الطلبات</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">طلبات المتقدمين</h2>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="status-filter">تصفية حسب الحالة</label>
            <select id="status-filter" v-model="statusFilter" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
              <option value="all">كل الحالات</option>
              <option value="submitted">مرسل للمراجعة</option>
              <option value="under_review">قيد المراجعة</option>
              <option value="accepted">مقبول</option>
              <option value="rejected">مرفوض</option>
            </select>
            <button type="button" :disabled="loading" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" @click="loadApplications">
              <i class="fa-solid fa-rotate-right" :class="loading ? 'fa-spin' : ''" aria-hidden="true"></i>
              تحديث
            </button>
          </div>
        </div>

        <div v-if="error" class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
          <i class="fa-solid fa-circle-exclamation ml-2" aria-hidden="true"></i>
          {{ error }}
        </div>

        <div v-if="loading" class="mt-6 grid gap-4 lg:grid-cols-2">
          <div v-for="placeholder in 4" :key="placeholder" class="h-64 animate-pulse rounded-2xl bg-slate-100"></div>
        </div>

        <div v-else-if="filteredApplications.length === 0" class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-2xl text-slate-300 shadow-sm">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
          </div>
          <h3 class="mt-5 text-lg font-bold text-slate-950">لا توجد طلبات بهذه الحالة</h3>
          <p class="mt-2 text-sm leading-6 text-slate-500">ستظهر هنا الطلبات التي تصل إلى الجهة بعد إرسالها من المتقدمين.</p>
          <button v-if="statusFilter !== 'all'" type="button" class="mt-5 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-800" @click="statusFilter = 'all'">عرض كل الطلبات</button>
        </div>

        <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
          <article v-for="application in filteredApplications" :key="application.id" class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/60 p-5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                  <i class="fa-solid fa-user-check text-lg" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-slate-500">طلب رقم #{{ application.id }}</p>
                  <h3 class="mt-1 truncate text-lg font-bold text-slate-950">{{ application.applicant?.name || 'متقدم غير محدد' }}</h3>
                  <p class="mt-1 truncate text-sm text-teal-700">{{ application.applicant?.email || 'بريد غير متوفر' }}</p>
                </div>
              </div>
              <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold" :class="statusClass(application.status)">
                <i :class="statusIcon(application.status)" aria-hidden="true"></i>
                {{ statusLabel(application.status) }}
              </span>
            </div>

            <div class="mt-5 rounded-xl bg-white p-4 ring-1 ring-slate-100">
              <p class="text-xs font-semibold text-slate-400">البرنامج</p>
              <p class="mt-1 font-bold text-slate-900">{{ application.program?.title || 'برنامج غير محدد' }}</p>
              <p class="mt-1 text-sm text-slate-500">{{ application.program?.type || 'نوع غير محدد' }}</p>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt class="text-slate-400">تاريخ الإرسال</dt>
                <dd class="mt-1 font-semibold text-slate-700">{{ formatDate(application.submitted_at || application.created_at, true) }}</dd>
              </div>
              <div>
                <dt class="text-slate-400">الهاتف</dt>
                <dd class="mt-1 truncate font-semibold text-slate-700">{{ application.applicant?.phone || 'غير متوفر' }}</dd>
              </div>
            </dl>

            <p v-if="application.notes" class="mt-4 line-clamp-2 text-sm leading-6 text-slate-600">
              <span class="font-bold text-slate-700">ملاحظات:</span>
              {{ application.notes }}
            </p>

            <div class="mt-auto flex flex-wrap gap-2 border-t border-slate-200 pt-5">
              <button v-if="canReview(application)" type="button" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-800" @click="openReview(application)">
                <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                مراجعة الطلب
              </button>
              <span v-else class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-500">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                تمت المراجعة
              </span>
              <RouterLink :to="{ name: 'application-details', params: { application: application.id } }" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-teal-300 hover:text-teal-700">
                التفاصيل
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
              </RouterLink>
            </div>
          </article>
        </div>
      </section>
    </main>

    <Transition name="modal">
      <div v-if="selectedApplication" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" @click.self="closeReview">
        <section class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-8" role="dialog" aria-modal="true" aria-labelledby="review-dialog-title">
          <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
              <p class="text-sm font-semibold text-teal-700">مراجعة طلب #{{ selectedApplication.id }}</p>
              <h2 id="review-dialog-title" class="mt-1 text-xl font-bold text-slate-950">{{ selectedApplication.applicant?.name || 'متقدم غير محدد' }}</h2>
              <p class="mt-1 text-sm text-slate-500">{{ selectedApplication.program?.title || 'برنامج غير محدد' }}</p>
            </div>
            <button type="button" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="إغلاق نافذة المراجعة" @click="closeReview">
              <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
          </div>

          <form class="mt-6 space-y-5" @submit.prevent="submitReview">
            <div class="grid gap-3 sm:grid-cols-3">
              <label v-for="option in [{ value: 'under_review', label: 'قيد المراجعة', icon: 'fa-magnifying-glass' }, { value: 'accepted', label: 'مقبول', icon: 'fa-circle-check' }, { value: 'rejected', label: 'مرفوض', icon: 'fa-circle-xmark' }]" :key="option.value" class="cursor-pointer rounded-2xl border p-4 transition" :class="reviewForm.status === option.value ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-100' : 'border-slate-200 hover:border-slate-300'">
                <input v-model="reviewForm.status" type="radio" name="review-status" :value="option.value" class="sr-only" />
                <i class="fa-solid mb-2 text-lg" :class="option.icon" aria-hidden="true"></i>
                <span class="block text-sm font-bold text-slate-800">{{ option.label }}</span>
              </label>
            </div>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">ملاحظات المراجعة</span>
              <textarea v-model="reviewForm.notes" rows="5" maxlength="5000" placeholder="اكتب ملاحظاتك أو أسباب القرار هنا" class="w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-100"></textarea>
              <span class="mt-1 block text-xs text-slate-400">{{ reviewForm.notes.length }} / 5000</span>
            </label>

            <div v-if="reviewError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
              <i class="fa-solid fa-circle-exclamation ml-2" aria-hidden="true"></i>
              {{ reviewError }}
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
              <button type="button" :disabled="submittingReview" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" @click="closeReview">إلغاء</button>
              <button type="submit" :disabled="submittingReview" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60">
                <i class="fa-solid" :class="submittingReview ? 'fa-spinner fa-spin' : 'fa-check'" aria-hidden="true"></i>
                {{ submittingReview ? 'جارٍ حفظ المراجعة...' : 'حفظ قرار المراجعة' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active,
.modal-enter-active,
.modal-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-0.5rem);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from section,
.modal-leave-to section {
  transform: translateY(0.5rem) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active,
  .modal-enter-active,
  .modal-leave-active {
    transition: none;
  }
}
</style>
