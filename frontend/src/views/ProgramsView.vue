<script setup>
import { onMounted, ref } from 'vue'
import programsService from '../services/programs'

const programs = ref([])
const loading = ref(false)
const error = ref(null)

function formatDate(value) {
  if (!value) {
    return 'غير محدد'
  }

  return new Intl.DateTimeFormat('ar-SA', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(value))
}

async function loadPrograms() {
  loading.value = true
  error.value = null

  try {
    const response = await programsService.list({ per_page: 100 })
    programs.value = response.data ?? []
  } catch (exception) {
    error.value = exception.response?.data?.message ?? 'تعذر تحميل البرامج حاليًا.'
  } finally {
    loading.value = false
  }
}

onMounted(loadPrograms)
</script>

<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-xl font-bold text-teal-700">ProgramHub</RouterLink>
        <div class="flex items-center gap-4">
          <RouterLink to="/dashboard" class="text-sm font-semibold text-slate-600 hover:text-teal-700">
            لوحة التحكم
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

    <section class="mx-auto max-w-6xl px-6 py-12">
      <div class="max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">ProgramHub</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-950 sm:text-4xl">البرامج المتاحة</h1>
        <p class="mt-4 leading-8 text-slate-600">
          استعرض البرامج والمنح والتدريبات المنشورة من المؤسسات المشاركة في المنصة.
        </p>
      </div>

      <div v-if="loading" class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="placeholder in 6"
          :key="placeholder"
          class="h-72 animate-pulse rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
        ></div>
      </div>

      <div
        v-else-if="error"
        class="mt-10 rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700"
        role="alert"
      >
        <div class="flex items-center gap-3">
          <i class="fa-solid fa-circle-exclamation text-lg" aria-hidden="true"></i>
          <p class="font-semibold">{{ error }}</p>
        </div>
        <button
          type="button"
          class="mt-4 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800"
          @click="loadPrograms"
        >
          إعادة المحاولة
        </button>
      </div>

      <div
        v-else-if="programs.length === 0"
        class="mt-10 rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200"
      >
        <i class="fa-solid fa-folder-open text-4xl text-slate-300" aria-hidden="true"></i>
        <h2 class="mt-4 text-xl font-bold text-slate-800">لا توجد برامج منشورة حاليًا</h2>
        <p class="mt-2 text-slate-500">ستظهر البرامج هنا عند نشرها من المؤسسات.</p>
      </div>

      <div v-else class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="program in programs"
          :key="program.id"
          class="flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-4">
            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
              {{ program.type || 'برنامج' }}
            </span>
            <i class="fa-solid fa-graduation-cap text-xl text-teal-600" aria-hidden="true"></i>
          </div>

          <h2 class="mt-5 text-xl font-bold leading-8 text-slate-950">{{ program.title }}</h2>
          <p class="mt-3 line-clamp-3 min-h-20 leading-7 text-slate-600">
            {{ program.description || 'لا يوجد وصف متاح لهذا البرنامج.' }}
          </p>

          <div class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm text-slate-600">
            <p class="flex items-center gap-3">
              <i class="fa-solid fa-building w-4 text-center text-teal-600" aria-hidden="true"></i>
              <span>{{ program.organization?.name || 'جهة غير محددة' }}</span>
            </p>
            <p class="flex items-center gap-3">
              <i class="fa-solid fa-location-dot w-4 text-center text-teal-600" aria-hidden="true"></i>
              <span>{{ program.location || 'الموقع غير محدد' }}</span>
            </p>
            <p class="flex items-center gap-3">
              <i class="fa-solid fa-laptop w-4 text-center text-teal-600" aria-hidden="true"></i>
              <span>{{ program.delivery_mode || 'طريقة التنفيذ غير محددة' }}</span>
            </p>
            <p class="flex items-center gap-3">
              <i class="fa-solid fa-calendar-days w-4 text-center text-teal-600" aria-hidden="true"></i>
              <span>آخر موعد: {{ formatDate(program.application_deadline) }}</span>
            </p>
          </div>

          <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-5 text-sm">
            <span class="font-semibold text-slate-500">
              <i class="fa-solid fa-users ml-1 text-teal-600" aria-hidden="true"></i>
              {{ program.capacity || 'غير محددة' }} مقعد
            </span>
            <span class="font-semibold text-emerald-700">منشور</span>
          </div>
        </article>
      </div>
    </section>
  </main>
</template>
