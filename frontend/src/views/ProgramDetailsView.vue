<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import programsService from '../services/programs'

const route = useRoute()
const program = ref(null)
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

onMounted(loadProgram)
</script>

<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-xl font-bold text-teal-700">ProgramHub</RouterLink>
        <div class="flex items-center gap-4">
          <RouterLink to="/programs" class="text-sm font-semibold text-slate-600 hover:text-teal-700">
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

      <article v-else class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
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
        </div>
      </article>
    </section>
  </main>
</template>
