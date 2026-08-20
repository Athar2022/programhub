<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import applicationsService from '../services/applications'
import programsService from '../services/programs'

const route = useRoute()
const authStore = useAuthStore()

const program = ref(null)
const loading = ref(false)
const error = ref(null)
const submitting = ref(false)
const submitError = ref(null)
const submitSuccess = ref(null)
const applicationForm = reactive({
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

async function loadProgram() {
  loading.value = true
  error.value = null

  try {
    const response = await programsService.show(route.params.program)
    program.value = response.program ?? null
  } catch (exception) {
    error.value = exception.response?.data?.message ?? 'تعذر تحميل تفاصيل البرنامج حاليًا.'
  } finally {
    loading.value = false
  }
}

function showSuccessToast(message) {
  submitSuccess.value = message

  if (successTimeoutId) {
    window.clearTimeout(successTimeoutId)
  }

  successTimeoutId = window.setTimeout(() => {
    submitSuccess.value = null
  }, 4000)
}

async function handleApply() {
  if (!authStore.isAuthenticated || authStore.user?.role !== 'applicant') {
    return
  }

  submitting.value = true
  submitError.value = null

  try {
    const response = await applicationsService.create(route.params.program, {
      notes: applicationForm.notes.trim() || null,
    })

    applicationForm.notes = ''
    showSuccessToast(response.message ?? 'تم إنشاء طلب التقديم بنجاح.')
  } catch (exception) {
    const responseMessage = exception.response?.data?.message

    submitError.value =
      responseMessage === 'You already have an application for this program.'
        ? 'لديك طلب تقديم موجود مسبقًا لهذا البرنامج.'
        : (exception.response?.data?.errors?.notes?.[0] ??
          responseMessage ??
          'تعذر إنشاء طلب التقديم حاليًا.')
  } finally {
    submitting.value = false
  }
}

onMounted(loadProgram)

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
        v-if="submitSuccess"
        class="fixed left-1/2 top-5 z-50 flex w-[calc(100%-2rem)] max-w-md -translate-x-1/2 items-center gap-3 rounded-xl bg-emerald-600 px-5 py-4 text-white shadow-lg"
        role="status"
        aria-live="polite"
      >
        <i class="fa-solid fa-circle-check text-xl" aria-hidden="true"></i>
        <p class="text-sm font-semibold">{{ submitSuccess }}</p>
      </div>
    </Transition>

    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-xl font-bold text-teal-700">ProgramHub</RouterLink>
        <div class="flex items-center gap-4">
          <RouterLink
            to="/programs"
            class="text-sm font-semibold text-slate-600 hover:text-teal-700"
          >
            البرامج
          </RouterLink>
          <RouterLink
            to="/login"
            class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
          >
            تسجيل الدخول
          </RouterLink>
        </div>
      </div>
    </header>

    <section class="mx-auto max-w-5xl px-6 py-12">
      <RouterLink
        to="/programs"
        class="inline-flex items-center gap-2 text-sm font-semibold text-teal-700 transition hover:text-teal-900"
      >
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        العودة إلى البرامج
      </RouterLink>

      <div
        v-if="loading"
        class="mt-8 h-[32rem] animate-pulse rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
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
          @click="loadProgram"
        >
          إعادة المحاولة
        </button>
      </div>

      <div
        v-else-if="!program"
        class="mt-8 rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200"
      >
        <i class="fa-solid fa-file-circle-question text-4xl text-slate-300" aria-hidden="true"></i>
        <h1 class="mt-4 text-xl font-bold text-slate-800">البرنامج غير موجود</h1>
        <p class="mt-2 text-slate-500">قد يكون البرنامج غير منشور أو لم يعد متاحًا.</p>
      </div>

      <article
        v-else
        class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
      >
        <div class="bg-gradient-to-l from-teal-700 to-teal-600 px-6 py-10 text-white sm:px-10">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-semibold">
              {{ program.type || 'برنامج' }}
            </span>
            <i class="fa-solid fa-graduation-cap text-3xl" aria-hidden="true"></i>
          </div>
          <h1 class="mt-6 text-3xl font-bold leading-tight sm:text-4xl">{{ program.title }}</h1>
          <p class="mt-4 flex items-center gap-2 text-teal-50">
            <i class="fa-solid fa-building" aria-hidden="true"></i>
            <span>{{ program.organization?.name || 'جهة غير محددة' }}</span>
          </p>
        </div>

        <div class="p-6 sm:p-10">
          <section>
            <h2 class="flex items-center gap-3 text-xl font-bold text-slate-950">
              <i class="fa-solid fa-circle-info text-teal-600" aria-hidden="true"></i>
              عن البرنامج
            </h2>
            <p class="mt-4 whitespace-pre-line leading-8 text-slate-600">
              {{ program.description || 'لا يوجد وصف متاح لهذا البرنامج.' }}
            </p>
          </section>

          <div class="mt-10 grid gap-4 border-t border-slate-100 pt-8 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-5">
              <p class="text-sm text-slate-500">
                <i class="fa-solid fa-location-dot ml-2 text-teal-600" aria-hidden="true"></i>
                الموقع
              </p>
              <p class="mt-2 font-semibold text-slate-900">{{ program.location || 'غير محدد' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-5">
              <p class="text-sm text-slate-500">
                <i class="fa-solid fa-laptop ml-2 text-teal-600" aria-hidden="true"></i>
                طريقة التنفيذ
              </p>
              <p class="mt-2 font-semibold text-slate-900">
                {{ program.delivery_mode || 'غير محددة' }}
              </p>
            </div>
            <div class="rounded-xl bg-slate-50 p-5">
              <p class="text-sm text-slate-500">
                <i class="fa-solid fa-calendar-days ml-2 text-teal-600" aria-hidden="true"></i>
                فترة البرنامج
              </p>
              <p class="mt-2 font-semibold text-slate-900">
                {{ formatDate(program.start_date) }} — {{ formatDate(program.end_date) }}
              </p>
            </div>
            <div class="rounded-xl bg-slate-50 p-5">
              <p class="text-sm text-slate-500">
                <i class="fa-solid fa-calendar-check ml-2 text-teal-600" aria-hidden="true"></i>
                آخر موعد للتقديم
              </p>
              <p class="mt-2 font-semibold text-slate-900">
                {{ formatDate(program.application_deadline) }}
              </p>
            </div>
            <div class="rounded-xl bg-slate-50 p-5">
              <p class="text-sm text-slate-500">
                <i class="fa-solid fa-users ml-2 text-teal-600" aria-hidden="true"></i>
                السعة
              </p>
              <p class="mt-2 font-semibold text-slate-900">
                {{ program.capacity || 'غير محددة' }} مقعد
              </p>
            </div>
            <div class="rounded-xl bg-slate-50 p-5">
              <p class="text-sm text-slate-500">
                <i class="fa-solid fa-circle-check ml-2 text-emerald-600" aria-hidden="true"></i>
                الحالة
              </p>
              <p class="mt-2 font-semibold text-emerald-700">منشور</p>
            </div>
          </div>

          <section class="mt-10 border-t border-slate-100 pt-8">
            <div
              v-if="authStore.isAuthenticated && authStore.user?.role === 'applicant'"
              class="rounded-2xl bg-teal-50 p-6 ring-1 ring-teal-100 sm:p-8"
            >
              <div class="flex items-start gap-3">
                <i
                  class="fa-solid fa-paper-plane mt-1 text-xl text-teal-700"
                  aria-hidden="true"
                ></i>
                <div>
                  <h2 class="text-xl font-bold text-slate-950">التقديم على البرنامج</h2>
                  <p class="mt-2 text-sm leading-7 text-slate-600">
                    ابدأ طلب التقديم بإضافة أي ملاحظات ترغب في إرفاقها. يمكنك استكمال بيانات الطلب
                    في الخطوات القادمة.
                  </p>
                </div>
              </div>

              <form class="mt-6" @submit.prevent="handleApply">
                <label
                  for="application-notes"
                  class="mb-2 block text-sm font-semibold text-slate-700"
                >
                  ملاحظات إضافية
                  <span class="font-normal text-slate-500">(اختياري)</span>
                </label>
                <textarea
                  id="application-notes"
                  v-model="applicationForm.notes"
                  rows="5"
                  maxlength="5000"
                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                  placeholder="اكتب أي ملاحظات أو معلومات إضافية هنا..."
                ></textarea>

                <div class="mt-2 flex items-center justify-between gap-4">
                  <span dir="ltr" class="text-xs text-slate-500">
                    {{ applicationForm.notes.length }} / 5000
                  </span>

                  <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    <i
                      class="fa-solid"
                      :class="submitting ? 'fa-spinner fa-spin' : 'fa-paper-plane'"
                      aria-hidden="true"
                    ></i>
                    {{ submitting ? 'جارٍ إنشاء الطلب...' : 'إنشاء طلب التقديم' }}
                  </button>
                </div>

                <div
                  v-if="submitError"
                  class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                  role="alert"
                >
                  <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                  <p class="font-semibold">{{ submitError }}</p>
                </div>
              </form>
            </div>

            <div
              v-else-if="!authStore.isAuthenticated"
              class="rounded-2xl bg-slate-50 p-6 text-center ring-1 ring-slate-200 sm:p-8"
            >
              <i class="fa-solid fa-lock text-2xl text-teal-700" aria-hidden="true"></i>
              <h2 class="mt-3 text-xl font-bold text-slate-950">هل ترغب في التقديم؟</h2>
              <p class="mt-2 text-sm leading-7 text-slate-600">
                سجّل الدخول بحساب متقدم حتى تتمكن من إنشاء طلب تقديم على هذا البرنامج.
              </p>
              <RouterLink
                to="/login"
                class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-800"
              >
                تسجيل الدخول للتقديم
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
              </RouterLink>
            </div>
          </section>
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
